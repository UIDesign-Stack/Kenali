<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'life_phase',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(TestAnswer::class);
    }

    public function result()
    {
        return $this->hasOne(TestResult::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function aiChatSessions()
    {
        return $this->hasMany(AiChatSession::class);
    }
}