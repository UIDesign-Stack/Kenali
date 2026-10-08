<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    /**
     * Hanya teks soal yang boleh diisi massal. Tiga kolom lain diatur oleh aturan di
     * Admin\QuestionController dan ditulis lewat assign properti / forceFill di sana:
     *  - sub_criteria_id: memindahkan soal ke sub-kriteria lain melewati aturan
     *    "minimal satu soal aktif per sub-kriteria".
     *  - is_active: hanya boleh berubah lewat toggleActive() yang menjaga aturan yang sama.
     *  - order: dihitung server (store) atau diatur lewat reorder().
     * Untuk seeder/factory yang butuh mengisinya, pakai forceFill() atau assign properti.
     */
    protected $fillable = [
        'question_text',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order'     => 'integer',
        ];
    }

    public function subCriteria(): BelongsTo
    {
        return $this->belongsTo(SubCriteria::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }
}
