<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status'          => session('status'),
            'avatarUrl'       => $request->user()->avatar_url,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->avatar;

        $newPath = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $newPath]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Foto profil diperbarui.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return back()->with('success', 'Foto profil dihapus.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Admin terakhir tidak boleh hilang
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return back()->withErrors([
                'password' => 'Akun admin terakhir tidak bisa dihapus.',
            ]);
        }

        // Psikolog dengan riwayat konsultasi: penghapusan akan menghapus riwayat pasien (cascade)
        if ($user->psychologistProfile?->consultations()->exists()) {
            return back()->withErrors([
                'password' => 'Akun psikolog dengan riwayat konsultasi tidak bisa dihapus. Hubungi admin untuk menonaktifkan akun.',
            ]);
        }

        $avatarPath = $user->avatar;

        $blocked = DB::transaction(function () use ($user) {
            if ($this->hasActiveConsultation($user)) {
                return true;
            }

            $user->notifications()->delete();
            $user->delete();

            return false;
        });

        if ($blocked) {
            return back()->withErrors([
                'password' => 'Akun tidak bisa dihapus karena masih memiliki konsultasi yang sedang berjalan. Selesaikan atau batalkan konsultasi tersebut terlebih dahulu.',
            ]);
        }

        if ($avatarPath) {
            Storage::disk('public')->delete($avatarPath);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function hasActiveConsultation(User $user): bool
    {
        $asClient = $user->consultations()
            ->whereIn('status', ConsultationStatus::activeStatusValues())
            ->exists();

        if ($asClient) {
            return true;
        }

        if ($user->psychologistProfile) {
            return $user->psychologistProfile->consultations()
                ->whereIn('status', ConsultationStatus::activeStatusValues())
                ->exists();
        }

        return false;
    }
}