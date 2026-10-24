<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'life_phase',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(TestResult::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }
}