<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_result_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_result_id')->constrained('test_results')->cascadeOnDelete();
            $table->foreignId('alternative_id')->constrained('alternatives')->cascadeOnDelete();
            $table->decimal('score', 6, 4);
            $table->unsignedTinyInteger('rank');
            $table->timestamps();

            $table->unique(['test_result_id', 'alternative_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_result_details');
    }
};
