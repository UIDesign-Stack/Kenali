<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alternatives', function (Blueprint $table) {
            // Keunikan nama dijaga di database juga, bukan hanya di Rule::unique()
            // (dua request bersamaan dengan nama sama bisa lolos validasi aplikasi).
            // Kalau migration gagal di sini, berarti sudah ada nama ganda: rapikan dulu.
            $table->unique('name');

            // Alternatif baru tidak boleh langsung aktif sebelum profil idealnya lengkap.
            $table->boolean('is_active')->default(false)->change();
        });

        // Riwayat hasil tes tidak boleh ikut terhapus diam-diam kalau sebuah alternatif
        // dihapus lewat jalur selain AlternativeController::destroy() (tinker, kode lain).
        Schema::table('test_result_details', function (Blueprint $table) {
            $table->dropForeign(['alternative_id']);
        });

        Schema::table('test_result_details', function (Blueprint $table) {
            $table->foreign('alternative_id')
                ->references('id')
                ->on('alternatives')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('test_result_details', function (Blueprint $table) {
            $table->dropForeign(['alternative_id']);
        });

        Schema::table('test_result_details', function (Blueprint $table) {
            $table->foreign('alternative_id')
                ->references('id')
                ->on('alternatives')
                ->cascadeOnDelete();
        });

        Schema::table('alternatives', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
            $table->dropUnique(['name']);
        });
    }
};
