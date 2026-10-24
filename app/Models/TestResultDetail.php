<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResultDetail extends Model
{
    use HasFactory;

    protected $fillable = [
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

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class);
    }

    public function alternative(): BelongsTo
    {
        return $this->belongsTo(Alternative::class);
    }
}
