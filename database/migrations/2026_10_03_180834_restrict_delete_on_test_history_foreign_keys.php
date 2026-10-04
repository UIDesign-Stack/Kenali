<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->foreign('question_id')->references('id')->on('questions')->restrictOnDelete();
        });

        Schema::table('test_result_details', function (Blueprint $table) {
            $table->dropForeign(['alternative_id']);
            $table->foreign('alternative_id')->references('id')->on('alternatives')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('test_answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
        });

        Schema::table('test_result_details', function (Blueprint $table) {
            $table->dropForeign(['alternative_id']);
            $table->foreign('alternative_id')->references('id')->on('alternatives')->cascadeOnDelete();
        });
    }
};