<?php

namespace App\Notifications;

use App\Models\ConsultationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewConsultationMessage extends Notification
{
    use Queueable;

    public function __construct(public ConsultationMessage $message) {}

    /**
     * Hanya database dan broadcast (lewat channel pribadi user), tidak ada email,
     * jadi tiap pesan chat tidak membanjiri inbox email lawan bicara.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $this->message->loadMissing('sender:id,name');

        $senderName = $this->message->sender?->name ?? 'seseorang';

        $url = $notifiable->hasRole('psikolog')
            ? route('psikolog.consultations.show', $this->message->consultation_id, false)
            : route('consultations.show', $this->message->consultation_id, false);

        return [
            'title'           => 'Pesan baru dari '.$senderName,
            'body'            => 'Buka konsultasi untuk membaca pesan.',
            'consultation_id' => $this->message->consultation_id,
            'url'             => $url,
        ];
    }
}
