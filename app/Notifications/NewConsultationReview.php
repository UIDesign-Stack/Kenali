<?php

namespace App\Notifications;

use App\Models\ConsultationReview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewConsultationReview extends Notification
{
    use Queueable;

    public function __construct(public ConsultationReview $review) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {

        $rating = max(1, min(5, (int) $this->review->rating));
        $stars  = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);

        return [
            'title' => 'Ulasan baru dari pasien',
            'body'      => $stars.' · Buka konsultasi untuk membaca ulasan.',
            'review_id' => $this->review->id,
            'url'       => route('psikolog.consultations.show', $this->review->consultation_id, false),
        ];
    }
}
