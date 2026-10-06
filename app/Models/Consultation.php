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
        'type',
        'status',
        'scheduled_at',
        'duration_minutes',
        'notes',
        'cancelled_reason',
    ];

    public const WARN_AFTER_DAYS = 5;
    public const CLOSE_AFTER_DAYS = 7;

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
    public function review()
    {
        return $this->hasOne(ConsultationReview::class);
    }

    public function autoCloseAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->status !== \App\Enums\ConsultationStatus::Scheduled->value || ! $this->scheduled_at) {
            return null;
        }

        $lastMessage = $this->messages()->reorder()->max('sent_at');

        $last = collect([$this->scheduled_at, $lastMessage ? \Illuminate\Support\Carbon::parse($lastMessage) : null])
            ->filter()
            ->max();

        if ($last->gt(now()->subDays(self::WARN_AFTER_DAYS))) {
            return null; // masih aktif, belum perlu banner
        }

        return $last->copy()->addDays(self::CLOSE_AFTER_DAYS);
    }
}
