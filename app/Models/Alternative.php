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
        'life_phase',
    ];

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