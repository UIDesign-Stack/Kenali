<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'question_id',
        'answer_value',
    ];

    protected function casts(): array
    {
        return [
            'answer_value' => 'integer',
        ];
    }

    public function testSession()
    {
        return $this->belongsTo(TestSession::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}