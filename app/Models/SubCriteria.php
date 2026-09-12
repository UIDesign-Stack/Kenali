<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCriteria extends Model
{
    use HasFactory;

    protected $table = 'sub_criteria';

    protected $fillable = [
        'criteria_id',
        'code',
        'name',
        'description',
    ];

    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function alternativeProfiles()
    {
        return $this->hasMany(AlternativeProfile::class);
    }
}