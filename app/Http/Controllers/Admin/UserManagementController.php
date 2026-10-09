<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConsultationStatus;
use App\Enums\LifePhase;
use App\Http\Controllers\Controller;
use App\Models\PsychologistProfile;
use App\Models\User;
use App\Notifications\AccountStatusChanged;
use App\Notifications\StaffAccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    private const ASSIGNABLE_ROLES = ['admin', 'psikolog', 'user'];

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

            'password' => ['required', 'confirmed', Rules\Password::min(12)->mixedCase()->numbers()],
            'role'     => ['required', 'in:admin,psikolog'],

            'license_number' => ['required_if:role,psikolog', 'nullable', 'string', 'max:255', 'unique:psychologist_profiles,license_number'],
            'specialization' => ['required_if:role,psikolog', 'nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = new User();
            $user->forceFill([
                'name'              => $validated['name'],
                'email'             => $validated['email'],
                'password'          => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ])->save();

            $user->assignRole($validated['role']);

            if ($validated['role'] === 'psikolog') {
                $profile = $user->psychologistProfile()->make([
                    'license_number' => $validated['license_number'],
                    'specialization' => $validated['specialization'],
                ]);

                $profile->forceFill([
                    'is_verified'  => true,
                    'is_available' => true,
                ])->save();
            }

            return $user;
        });

        Log::info('Akun staf dibuat', [
            'new_user_id' => $user->id,
            'role'        => $validated['role'],
            'admin_id'    => $request->user()->id,
        ]);

        rescue(fn () => $user->notify(new StaffAccountCreated($validated['role'])));

        return redirect()->route('admin.users.index')
            ->with('success', "Akun {$validated['role']} untuk {$user->name} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        $user->load('roles:name');

        return Inertia::render('Admin/Users/Edit', [
            'user'             => $user,
            'roles'            => Role::whereIn('name', self::ASSIGNABLE_ROLES)->pluck('name'),
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
            'role'       => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()->withErrors([
                'role' => 'Tidak bisa mengubah role akun sendiri dari admin.',
            ]);
        }

        $wasPsikolog      = $user->hasRole('psikolog');
        $willBePsikolog   = $validated['role'] === 'psikolog';
        $oldRole          = $user->getRoleNames()->first();

        if ($willBePsikolog && ! $wasPsikolog && ! $user->psychologistProfile) {
            return back()->withErrors([
                'role' => 'Role psikolog hanya bisa diberikan lewat menu "Tambah Psikolog Baru" karena membutuhkan data lisensi dan spesialisasi.',
            ]);
        }

        $actorId = $request->user()->id;

        $outcome = DB::transaction(function () use ($user, $validated, $wasPsikolog, $willBePsikolog, $actorId) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($wasPsikolog && ! $willBePsikolog) {
                $profile = PsychologistProfile::where('user_id', $locked->id)->lockForUpdate()->first();

                if ($profile) {
                    $hasActive = $profile->consultations()
                        ->whereIn('status', ConsultationStatus::activeStatusValues())
                        ->exists();

                    if ($hasActive) {
                        return ['status' => 'has_active_consultation'];
                    }

                    $profile->forceFill(['is_verified' => false, 'is_available' => false])->save();
                }
            }

            $locked->fill([
                'name'       => $validated['name'],
                'email'      => $validated['email'],
                'phone'      => $validated['phone'] ?? null,
                'life_phase' => $validated['life_phase'] ?? null,
            ]);

            $changed = array_keys($locked->getDirty());

            $emailReset = in_array('email', $changed, true) && $locked->id !== $actorId;

            if ($emailReset) {
                $locked->email_verified_at = null;
            }

            $locked->save();

            $locked->syncRoles([$validated['role']]);

            return ['status' => 'ok', 'changed' => $changed, 'email_reset' => $emailReset];
        });

        if ($outcome['status'] === 'has_active_consultation') {
            return back()->withErrors([
                'role' => 'Tidak bisa mengubah role: psikolog ini masih memiliki konsultasi yang sedang berjalan.',
            ]);
        }

        if ($outcome['email_reset']) {
            rescue(fn () => $user->refresh()->sendEmailVerificationNotification());
        }

        Log::info('Data user diperbarui oleh admin', [
            'user_id'        => $user->id,
            'admin_id'       => $request->user()->id,
            'changed_fields' => $outcome['changed'],
            'old_role'       => $oldRole,
            'new_role'       => $validated['role'],
        ]);

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

        $isActive = DB::transaction(function () use ($user) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($locked->is_active && $this->hasActiveConsultation($locked)) {
                return null;
            }

            $newState = ! $locked->is_active;


            $locked->forceFill(['is_active' => $newState])->save();

            if (! $newState) {

                $locked->forceFill(['remember_token' => Str::random(60)])->save();

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))
                        ->where('user_id', $locked->id)
                        ->delete();
                }

                $profile = PsychologistProfile::where('user_id', $locked->id)->lockForUpdate()->first();
                $profile?->forceFill(['is_available' => false])->save();
            }

            return $newState;
        });

        if ($isActive === null) {
            return back()->withErrors([
                'user' => "Akun {$user->name} tidak bisa dinonaktifkan karena masih memiliki konsultasi yang sedang berjalan.",
            ]);
        }

        Log::info('Status akun diubah oleh admin', [
            'user_id'   => $user->id,
            'admin_id'  => $request->user()->id,
            'is_active' => $isActive,
        ]);

        rescue(fn () => $user->refresh()->notify(new AccountStatusChanged($isActive)));

        return back()->with('success', $isActive
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        Log::info('Link reset password dikirim oleh admin', [
            'user_id'  => $user->id,
            'admin_id' => $request->user()->id,
            'status'   => $status,
        ]);

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
