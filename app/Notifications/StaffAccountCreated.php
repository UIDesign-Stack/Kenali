<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $role) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Kenali kamu sudah dibuat')
            ->line("Admin telah membuatkan akun {$this->role} untuk kamu di Kenali.")
            ->line("Email login: {$notifiable->email}")
            ->line('Silakan login menggunakan password yang sudah diberikan admin secara terpisah, lalu segera ganti password demi keamanan.')
            ->action('Login ke Kenali', route('login'));
    }
}