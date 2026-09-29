<?php

use App\Models\Consultation;
use Illuminate\Support\Facades\Broadcast;


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('consultation.{consultationId}', function ($user, $consultationId) {
    $consultation = Consultation::with('psychologistProfile')->find($consultationId);

    if (! $consultation) {
        return false;
    }

    $isOwner = $consultation->user_id === $user->id;
    $isAssignedPsychologist = $consultation->psychologistProfile?->user_id === $user->id;

    return $isOwner || $isAssignedPsychologist;
});