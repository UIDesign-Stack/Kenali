<?php

namespace App\Events;

use App\Models\ConsultationMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ConsultationMessageSent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    /**
     * Kalau event ini dipancarkan di dalam DB::transaction, pancarkan SETELAH commit,
     * supaya pesan tidak sampai ke klien sebelum (atau tanpa) benar-benar tersimpan.
     */
    public bool $afterCommit = true;

    public function __construct(public ConsultationMessage $message) {}

    /**
     * PrivateChannel: hanya pemilik konsultasi dan psikolognya yang lolos aturan di
     * routes/channels.php. Jangan diganti Channel biasa, karena payload di bawah memuat isi pesan.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.'.$this->message->consultation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'consultation_id' => $this->message->consultation_id,
            'message'         => $this->message->message,

            'sent_at'         => $this->message->sent_at
                ? Carbon::parse($this->message->sent_at)->toIso8601String()
                : null,
            'read_at'         => null,
            'sender'          => [
                'id'   => $this->message->sender?->id,
                'name' => $this->message->sender?->name,
            ],
        ];
    }
}
