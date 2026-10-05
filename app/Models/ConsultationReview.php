<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'psychologist_profile_id',
        'user_id',
        'rating',
        'comment',
        'is_hidden',
        'reply',
        'replied_at',
        'reply_hidden',
    ];

    protected function casts(): array
    {
        return [
            'rating'       => 'integer',
            'is_hidden'    => 'boolean',
            'reply_hidden' => 'boolean',
            'replied_at'   => 'datetime',
        ];
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function psychologistProfile()
    {
        return $this->belongsTo(PsychologistProfile::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Ulasan yang boleh tampil ke publik. */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /** Pasien boleh mengubah ulasan dalam 24 jam sejak dikirim. */
    public function isEditableByAuthor(): bool
    {
        return $this->created_at->gt(now()->subHours(24));
    }

    /** Psikolog boleh mengubah balasan dalam 24 jam sejak dibalas. */
    public function isReplyEditable(): bool
    {
        return $this->replied_at === null || $this->replied_at->gt(now()->subHours(24));
    }
}