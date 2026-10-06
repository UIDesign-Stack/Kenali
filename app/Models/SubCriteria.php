<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'local_weight' => 'float',
        ];
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(Criteria::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function alternativeProfiles(): HasMany
    {
        return $this->hasMany(AlternativeProfile::class);
    }
}
