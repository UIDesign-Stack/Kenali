<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestResult extends Model
{
    use HasFactory;

    protected $fillable = [];

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TestResultDetail::class)->orderBy('rank');
    }

    public function topDetail(): HasOne
    {
        return $this->hasOne(TestResultDetail::class)->orderBy('rank');
    }
}
