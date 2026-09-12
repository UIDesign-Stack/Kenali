<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criteria extends Model
{
    use HasFactory;

    protected $table = 'criteria';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function subCriteria()
    {
        return $this->hasMany(SubCriteria::class);
    }

    public function weights()
    {
        return $this->hasMany(CriteriaWeight::class);
    }

    public function latestWeight()
    {
        return $this->hasOne(CriteriaWeight::class)->latestOfMany();
    }
}