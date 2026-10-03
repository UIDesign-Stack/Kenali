<?php

namespace App\Actions;

use App\Events\ConsultationMessagesRead;
use App\Models\Consultation;
use App\Models\User;
use App\Notifications\NewConsultationMessage;

class MarkConsultationMessagesAsRead
{
    public function handle(Consultation $consultation, User $reader): void
    {
        $readAt = now();

        $updated = $consultation->messages()
            ->reorder()
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);

        if ($updated > 0) {
            broadcast(new ConsultationMessagesRead(
                $consultation->id,
                $reader->id,
                $readAt->toIso8601String()
            ))->toOthers();
        }

        $reader->unreadNotifications()
            ->where('type', NewConsultationMessage::class)
            ->where('data->consultation_id', $consultation->id)
            ->update(['read_at' => $readAt]);
    }
}