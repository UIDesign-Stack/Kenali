<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('weight');
        });

        // Bobot lama: hanya baris terbaru per kriteria yang aktif
        $latestIds = DB::table('criteria_weights')
            ->selectRaw('MAX(id) as id')
            ->groupBy('criteria_id')
            ->pluck('id');

        DB::table('criteria_weights')->whereNotIn('id', $latestIds)->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};