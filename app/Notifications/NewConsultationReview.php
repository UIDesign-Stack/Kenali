<?php

namespace App\Notifications;

use App\Models\ConsultationReview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

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
        $stars = str_repeat('★', $this->review->rating).str_repeat('☆', 5 - $this->review->rating);

        return [
            'title'     => 'Ulasan baru dari pasien',
            'body'      => $this->review->comment
                ? $stars.' · '.Str::limit($this->review->comment, 60)
                : $stars,
            'review_id' => $this->review->id,
            'url'       => route('psikolog.consultations.show', $this->review->consultation_id),
        ];
    }
}
