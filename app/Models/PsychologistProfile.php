<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_available',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'  => 'boolean',
            'is_available' => 'boolean',
            'rating_avg'   => 'float',
            'rating_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
    public function reviews()
    {
        return $this->hasMany(ConsultationReview::class);
    }
}