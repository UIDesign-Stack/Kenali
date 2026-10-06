<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlternativeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'alternative_id',
        'sub_criteria_id',
        'ideal_score',
    ];

    protected function casts(): array
    {
        return [
            'ideal_score' => 'decimal:2',
        ];
    }

    public function alternative(): BelongsTo
    {
        return $this->belongsTo(Alternative::class);
    }

    public function subCriteria(): BelongsTo
    {
        return $this->belongsTo(SubCriteria::class);
    }
}
