<?php

namespace App\Events;

use App\Models\Consultation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ConsultationStatusChanged implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public Consultation $consultation) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.'.$this->consultation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }

    public function broadcastWith(): array
    {

        return [
            'consultation_id' => $this->consultation->id,
            'status'          => $this->consultation->status,
        ];
    }
}