<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'user_id',
        'psychologist_profile_id',
        'status',
        'scheduled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function testSession()
    {
        return $this->belongsTo(TestSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function psychologistProfile()
    {
        return $this->belongsTo(PsychologistProfile::class);
    }

    public function messages()
    {
        return $this->hasMany(ConsultationMessage::class)->orderBy('sent_at');
    }
}