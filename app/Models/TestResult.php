<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
    ];

    public function testSession()
    {
        return $this->belongsTo(TestSession::class);
    }

    public function details()
    {
        return $this->hasMany(TestResultDetail::class)->orderBy('rank');
    }

    public function topDetail()
    {
        return $this->hasOne(TestResultDetail::class)->orderBy('rank');
    }
}