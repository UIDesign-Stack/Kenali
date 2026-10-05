<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->unique()->constrained('consultations')->cascadeOnDelete();
            $table->foreignId('psychologist_profile_id')->constrained('psychologist_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('comment', 500)->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->string('reply', 500)->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->boolean('reply_hidden')->default(false);
            $table->timestamps();

            $table->index(['psychologist_profile_id', 'is_hidden', 'created_at'], 'cr_profile_visible_idx');
        });

        Schema::table('psychologist_profiles', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('psychologist_profiles', function (Blueprint $table) {
            $table->dropColumn(['rating_avg', 'rating_count']);
        });

        Schema::dropIfExists('consultation_reviews');
    }
};