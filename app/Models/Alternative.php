<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alternative extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'life_phase',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function profiles()
    {
        return $this->hasMany(AlternativeProfile::class);
    }

    public function subCriteria()
    {
        return $this->belongsToMany(SubCriteria::class, 'alternative_profiles')
            ->withPivot('ideal_score')
            ->withTimestamps();
    }

    public function resultDetails()
    {
        return $this->hasMany(TestResultDetail::class);
    }
}