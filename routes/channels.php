<?php

use App\Models\Consultation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('consultation.{consultationId}', function ($user, $consultationId) {

    if (! $user->is_active) {
        return false;
    }

    if (! ctype_digit((string) $consultationId)) {
        return false;
    }

    $consultation = Consultation::query()
        ->select('id', 'user_id', 'psychologist_profile_id')
        ->with('psychologistProfile:id,user_id')
        ->find($consultationId);

    if (! $consultation) {
        return false;
    }

    $userId = (int) $user->id;

    $isOwner                = (int) $consultation->user_id === $userId;
    $isAssignedPsychologist = (int) $consultation->psychologistProfile?->user_id === $userId;

    return $isOwner || $isAssignedPsychologist;
});
