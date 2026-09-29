<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PsychologistVerified extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun psikolog kamu sudah terverifikasi')
            ->line('Selamat! Akun psikolog kamu di Kenali sudah diverifikasi oleh admin.')
            ->line('Kamu sekarang bisa mulai menerima permintaan konsultasi dari user.')
            ->action('Lihat Konsultasi Masuk', route('psikolog.consultations.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Akun terverifikasi',
            'body'  => 'Akun psikolog kamu sudah diverifikasi admin dan siap menerima konsultasi.',
            'url'   => route('psikolog.consultations.index'),
        ];
    }
}