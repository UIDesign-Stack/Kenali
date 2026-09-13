<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    private const ACTIVE_CONSULTATION_STATUSES = ['pending', 'ongoing', 'in_progress'];

    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
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

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($this->hasActiveConsultation($user)) {
            return back()->withErrors([
                'password' => 'Akun tidak bisa dihapus karena masih memiliki konsultasi yang sedang berjalan. Selesaikan atau batalkan konsultasi tersebut terlebih dahulu.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function hasActiveConsultation($user): bool
    {

        $asClient = $user->consultations()
            ->whereIn('status', self::ACTIVE_CONSULTATION_STATUSES)
            ->exists();

        if ($asClient) {
            return true;
        }

        if ($user->psychologistProfile) {
            return $user->psychologistProfile->consultations()
                ->whereIn('status', self::ACTIVE_CONSULTATION_STATUSES)
                ->exists();
        }

        return false;
    }
}