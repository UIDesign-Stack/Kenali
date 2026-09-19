<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConsultationStatus;
use App\Enums\LifePhase;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();

        $query = User::with('roles:id,name')
            ->withCount('testSessions')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('email', 'like', '%'.addcslashes($search, '%_\\').'%');
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function show(User $user)
    {
        $user->load('roles:id,name', 'psychologistProfile');

        $testSessions = $user->testSessions()
            ->with([
                'result.details' => fn ($q) => $q->orderBy('rank')->with('alternative:id,name'),
            ])
            ->latest('started_at')
            ->get()
            ->each(function ($session) {
                if ($session->result) {
                    $session->result->setRelation('topDetail', $session->result->details->first());
                    $session->result->unsetRelation('details');
                }
            });

        return Inertia::render('Admin/Users/Show', [
            'user'         => $user,
            'testSessions' => $testSessions,
        ]);
    }

    public function createStaff()
    {
        return Inertia::render('Admin/Users/CreateStaff');
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role'     => ['required', 'in:admin,psikolog'],

            'license_number' => ['required_if:role,psikolog', 'nullable', 'string', 'max:255'],
            'specialization' => ['required_if:role,psikolog', 'nullable', 'string', 'max:255'],
        ]);

        // Dibungkus transaction: User::create() + assignRole() + (opsional)
        // psychologistProfile()->create() harus atomic, mencegah user dengan
        // role 'psikolog' tapi tanpa psychologistProfile kalau salah satu
        // langkah gagal di tengah.
        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'              => $validated['name'],
                'email'             => $validated['email'],
                'password'          => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            $user->assignRole($validated['role']);

            if ($validated['role'] === 'psikolog') {
                $user->psychologistProfile()->create([
                    'license_number' => $validated['license_number'],
                    'specialization' => $validated['specialization'],
                    'is_verified'    => true,
                    'is_available'   => true,
                ]);
            }

            return $user;
        });

        return redirect()->route('admin.users.index')
            ->with('success', "Akun {$validated['role']} untuk {$user->name} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        $user->load('roles:name');

        return Inertia::render('Admin/Users/Edit', [
            'user'             => $user,
            'roles'            => Role::pluck('name'),
            'lifePhaseOptions' => LifePhase::assignableToUserOptions(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'      => ['nullable', 'string', 'max:20'],
            'life_phase' => ['nullable', Rule::in(LifePhase::assignableToUserValues())],
            'role'       => ['required', Rule::in(Role::pluck('name'))],
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()->withErrors([
                'role' => 'Tidak bisa mengubah role akun sendiri dari admin.',
            ]);
        }

        // Kalau user ini SEDANG punya role 'psikolog' dan role barunya BUKAN
        // 'psikolog' lagi, pastikan tidak ada konsultasi aktif yang akan
        // "kehilangan" psikolognya di tengah sesi.
        $wasPsikolog = $user->hasRole('psikolog');
        $willStayPsikolog = $validated['role'] === 'psikolog';

        if ($wasPsikolog && ! $willStayPsikolog && $this->hasActiveConsultationAsPsychologist($user)) {
            return back()->withErrors([
                'role' => 'Tidak bisa mengubah role: psikolog ini masih memiliki konsultasi yang sedang berjalan.',
            ]);
        }

        $user->update([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'life_phase' => $validated['life_phase'] ?? null,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', "Data {$user->name} berhasil diperbarui.");
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors([
                'user' => 'Tidak bisa menonaktifkan akun sendiri.',
            ]);
        }

        $isDeactivating = $user->is_active;

        if ($isDeactivating && $this->hasActiveConsultation($user)) {
            return back()->withErrors([
                'user' => "Akun {$user->name} tidak bisa dinonaktifkan karena masih memiliki konsultasi yang sedang berjalan.",
            ]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan.");
    }

    public function resetPassword(User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', "Link reset password telah dikirim ke {$user->email}.")
            : back()->withErrors(['email' => __($status)]);
    }

    private function hasActiveConsultation(User $user): bool
    {
        $asClient = $user->consultations()
            ->whereIn('status', ConsultationStatus::activeStatusValues())
            ->exists();

        return $asClient || $this->hasActiveConsultationAsPsychologist($user);
    }

    private function hasActiveConsultationAsPsychologist(User $user): bool
    {
        if (! $user->psychologistProfile) {
            return false;
        }

        return $user->psychologistProfile->consultations()
            ->whereIn('status', ConsultationStatus::activeStatusValues())
            ->exists();
    }
}