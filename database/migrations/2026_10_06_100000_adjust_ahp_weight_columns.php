<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_criteria', function (Blueprint $table) {
            $table->decimal('local_weight', 8, 6)->nullable()->change();
        });

        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->decimal('weight', 8, 6)->change();
            $table->decimal('cr_value', 8, 6)->nullable()->change();
        });

        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->dropForeign(['set_by']);
        });

        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->unsignedBigInteger('set_by')->nullable()->change();
            $table->foreign('set_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->dropForeign(['set_by']);
        });

        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->unsignedBigInteger('set_by')->nullable(false)->change();
            $table->foreign('set_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->decimal('weight', 5, 4)->change();
            $table->decimal('cr_value', 5, 4)->nullable()->change();
        });

        Schema::table('sub_criteria', function (Blueprint $table) {
            $table->decimal('local_weight', 5, 4)->nullable()->change();
        });
    }
};
