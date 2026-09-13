<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_session_id',
        'is_flagged',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at'   => 'datetime',
            'is_flagged' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function testSession()
    {
        return $this->belongsTo(TestSession::class);
    }

    public function messages()
    {
        return $this->hasMany(AiChatMessage::class)->orderBy('created_at');
    }
}