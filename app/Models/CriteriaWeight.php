<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CriteriaWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'criteria_id',
        'weight',
        'set_by',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
        ];
    }

    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}