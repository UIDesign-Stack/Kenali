<?php

use App\Models\Consultation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Di sini didaftarkan semua private channel yang boleh diakses. Laravel
| otomatis membuat endpoint /broadcasting/auth yang memanggil callback
| ini setiap kali Echo mencoba subscribe ke sebuah private channel.
|
*/

/**
 * Channel per-konsultasi. Hanya user pemilik konsultasi ATAU psikolog
 * yang ditugaskan di konsultasi itu yang boleh subscribe -- supaya orang
 * lain tidak bisa menguping percakapan konsultasi orang lain.
 */
Broadcast::channel('consultation.{consultationId}', function ($user, $consultationId) {
    // Eager load psychologistProfile supaya tidak lazy-load query tambahan
    // (dan tetap aman kalau Model::preventLazyLoading() diaktifkan di
    // AppServiceProvider).
    $consultation = Consultation::with('psychologistProfile')->find($consultationId);

    if (! $consultation) {
        return false;
    }

    $isOwner = $consultation->user_id === $user->id;
    $isAssignedPsychologist = $consultation->psychologistProfile?->user_id === $user->id;

    return $isOwner || $isAssignedPsychologist;
});