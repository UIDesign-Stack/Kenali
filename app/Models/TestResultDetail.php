<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestResultDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_result_id',
        'alternative_id',
        'score',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:4',
            'rank'  => 'integer',
        ];
    }

    public function testResult()
    {
        return $this->belongsTo(TestResult::class);
    }

    public function alternative()
    {
        return $this->belongsTo(Alternative::class);
    }
}