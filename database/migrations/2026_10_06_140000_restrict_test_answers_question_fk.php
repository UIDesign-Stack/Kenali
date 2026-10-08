<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jawaban user tidak boleh ikut terhapus diam-diam kalau sebuah soal dihapus lewat jalur
        // selain QuestionController::destroy() (tinker, kode lain, atau cascade dari sub-kriteria).
        Schema::table('test_answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });

        Schema::table('test_answers', function (Blueprint $table) {
            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('test_answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });

        Schema::table('test_answers', function (Blueprint $table) {
            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->cascadeOnDelete();
        });
    }
};
