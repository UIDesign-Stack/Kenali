<?php

namespace App\Notifications;

use App\Models\ConsultationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewConsultationMessage extends Notification
{
    use Queueable;

    public function __construct(public ConsultationMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $this->message->loadMissing('sender:id,name');

        $url = $notifiable->hasRole('psikolog')
            ? route('psikolog.consultations.show', $this->message->consultation_id)
            : route('consultations.show', $this->message->consultation_id);

        return [
            'title'           => 'Pesan baru dari '.$this->message->sender->name,
            'body'            => Str::limit($this->message->message, 80),
            'consultation_id' => $this->message->consultation_id,
            'url'             => $url,
        ];
    }
}