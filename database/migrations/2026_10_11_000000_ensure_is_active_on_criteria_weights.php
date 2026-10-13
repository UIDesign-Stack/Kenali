<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration 2026_10_03_183753_add_is_active_to_criteria_weights_table tercatat "Ran", tetapi
     * kolomnya tidak ada di database (database kemungkinan dipulihkan dari cadangan lama atau
     * diubah manual). Migration ini aman dijalankan di database mana pun: kolom hanya dibuat
     * kalau belum ada, dan penandaan bobot aktif dihitung ulang.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('criteria_weights', 'is_active')) {
            Schema::table('criteria_weights', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('weight');
            });
        }

        // Hanya baris terbaru per kriteria yang aktif (aturan yang sama dengan AhpController).
        $latestIds = DB::table('criteria_weights')
            ->selectRaw('MAX(id) as id')
            ->groupBy('criteria_id')
            ->pluck('id');

        DB::table('criteria_weights')->whereIn('id', $latestIds)->update(['is_active' => true]);
        DB::table('criteria_weights')->whereNotIn('id', $latestIds)->update(['is_active' => false]);
    }

    public function down(): void
    {
        // Sengaja kosong: kolom ini juga dibuat oleh migration sebelumnya, jadi tidak boleh
        // dihapus dari sini.
    }
};
