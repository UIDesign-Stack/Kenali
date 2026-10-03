<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ConsultationMessagesRead implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $consultationId,
        public int $readerId,
        public string $readAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.'.$this->consultationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'consultation_id' => $this->consultationId,
            'reader_id'       => $this->readerId,
            'read_at'         => $this->readAt,
        ];
    }
}