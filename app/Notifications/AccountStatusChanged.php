<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $isActive) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = new MailMessage;

        if ($this->isActive) {
            return $message
                ->subject('Akun kamu diaktifkan kembali')
                ->line('Akun Kenali kamu sudah diaktifkan kembali oleh admin. Kamu bisa login seperti biasa.');
        }

        return $message
            ->subject('Akun kamu dinonaktifkan')
            ->line('Akun Kenali kamu dinonaktifkan sementara oleh admin.')
            ->line('Kalau merasa ini keliru, silakan hubungi admin untuk klarifikasi.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->isActive ? 'Akun diaktifkan' : 'Akun dinonaktifkan',
            'body'  => $this->isActive
                ? 'Akunmu sudah diaktifkan kembali oleh admin.'
                : 'Akunmu dinonaktifkan sementara oleh admin.',
            'url' => route('dashboard'),
        ];
    }
}