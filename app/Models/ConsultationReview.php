<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationReview extends Model
{
    use HasFactory;

    /**
     * `is_hidden` dan `reply_hidden` sengaja TIDAK dimasukkan: keduanya flag moderasi
     * yang hanya boleh diubah admin lewat PsychologistManagementController
     * (toggleReviewHidden / toggleReplyHidden, memakai forceFill). Dengan begitu penulis
     * ulasan atau psikolog tidak bisa membuka sembunyian lewat form mereka sendiri.
     */
    protected $fillable = [
        'consultation_id',
        'psychologist_profile_id',
        'user_id',
        'rating',
        'comment',
        'reply',
        'replied_at',
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

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function psychologistProfile(): BelongsTo
    {
        return $this->belongsTo(PsychologistProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Ulasan yang boleh tampil ke publik. */
    public function scopeVisible(Builder $query): Builder
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
