<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriteriaWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'criteria_id',
        'weight',
        'cr_value',
        'note',
        'set_by',
    ];

    protected function casts(): array
    {
        return [
            'weight'   => 'float',
            'cr_value' => 'float',
        ];
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(Criteria::class);
    }

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
