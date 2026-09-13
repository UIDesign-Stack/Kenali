<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'sender_id',
        'message',
        'is_read',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'is_read' => 'boolean',
        ];
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}