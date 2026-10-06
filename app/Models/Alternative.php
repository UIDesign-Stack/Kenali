<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alternative extends Model
{
    use HasFactory;

    /**
     * `is_active` sengaja TIDAK dimasukkan: status aktif hanya boleh diubah lewat
     * AlternativeController::toggleActive(), yang memeriksa kelengkapan profil dan
     * aturan "minimal satu alternatif aktif". Untuk mengisinya di kode lain
     * (seeder/test), pakai forceFill() atau assign properti langsung.
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
        'life_phase',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(AlternativeProfile::class);
    }

    public function subCriteria(): BelongsToMany
    {
        return $this->belongsToMany(SubCriteria::class, 'alternative_profiles')
            ->withPivot('ideal_score')
            ->withTimestamps();
    }

    public function resultDetails(): HasMany
    {
        return $this->hasMany(TestResultDetail::class);
    }
}
