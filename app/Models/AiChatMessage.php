<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_chat_session_id',
        'sender',
        'message',
        'is_flagged',
    ];

    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
        ];
    }

    public function chatSession()
    {
        return $this->belongsTo(AiChatSession::class, 'ai_chat_session_id');
    }
}