<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alternative_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alternative_id')->constrained('alternatives')->cascadeOnDelete();
            $table->foreignId('sub_criteria_id')->constrained('sub_criteria')->cascadeOnDelete();
            $table->decimal('ideal_score', 4, 2);
            $table->timestamps();

            $table->unique(['alternative_id', 'sub_criteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternative_profiles');
    }
};
