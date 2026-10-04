<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_criteria_id',
        'question_text',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function subCriteria()
    {
        return $this->belongsTo(SubCriteria::class);
    }

    public function answers()
    {
        return $this->hasMany(TestAnswer::class);
    }
}