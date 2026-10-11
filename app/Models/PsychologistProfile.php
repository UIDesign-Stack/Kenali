<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PsychologistProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'specialization',
        'bio',
        'photo',
        'years_of_experience',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'         => 'boolean',
            'is_available'        => 'boolean',
            'rating_avg'          => 'float',
            'rating_count'        => 'integer',
            'years_of_experience' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ConsultationReview::class);
    }
}
