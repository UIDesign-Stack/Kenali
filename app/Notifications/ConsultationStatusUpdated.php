<?php

namespace App\Notifications;

use App\Models\Consultation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsultationStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $context  Salah satu: 'requested', 'scheduled', 'rejected', 'cancelled_after_scheduled', 'completed', 'force_cancelled'
     */
    public function __construct(
        public Consultation $consultation,
        public string $context,
    ) {}

    public function via(object $notifiable): array
    {
        return match ($this->context) {
            'requested' => ['database', 'broadcast'],
            default     => ['database', 'mail', 'broadcast'],
        };
    }

    protected function title(): string
    {
        return match ($this->context) {
            'requested'                 => 'Permintaan konsultasi baru',
            'scheduled'                 => 'Konsultasimu diterima & dijadwalkan',
            'rejected'                  => 'Permintaan konsultasi ditolak',
            'cancelled_after_scheduled' => 'Konsultasi terjadwal dibatalkan psikolog',
            'completed'                 => 'Konsultasi selesai',
            'force_cancelled'           => 'Konsultasi dibatalkan oleh admin',
            default                     => 'Update konsultasi',
        };
    }

    protected function body(): string
    {
        $this->consultation->loadMissing('user:id,name', 'psychologistProfile.user:id,name');

        return match ($this->context) {
            'requested'                 => "{$this->consultation->user->name} mengajukan konsultasi baru.",
            'scheduled'                 => "Psikolog {$this->consultation->psychologistProfile->user->name} menerima & menjadwalkan konsultasimu.",
            'rejected'                  => "Permintaan konsultasimu ditolak. Alasan: {$this->consultation->cancelled_reason}",
            'cancelled_after_scheduled' => "Konsultasi terjadwalmu dengan {$this->consultation->psychologistProfile->user->name} dibatalkan. Alasan: {$this->consultation->cancelled_reason}",
            'completed'                 => "Konsultasi dengan {$this->consultation->psychologistProfile->user->name} sudah ditandai selesai.",
            'force_cancelled'           => "Konsultasi dibatalkan oleh admin. Alasan: {$this->consultation->cancelled_reason}",
            default                     => 'Ada pembaruan pada konsultasimu.',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->line($this->body())
            ->action('Lihat Konsultasi', $this->url($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'           => $this->title(),
            'body'            => $this->body(),
            'consultation_id' => $this->consultation->id,
            'url'             => $this->url($notifiable),
        ];
    }

    protected function url(object $notifiable): string
    {
        return $notifiable->hasRole('psikolog')
            ? route('psikolog.consultations.show', $this->consultation->id)
            : route('consultations.show', $this->consultation->id);
    }
}