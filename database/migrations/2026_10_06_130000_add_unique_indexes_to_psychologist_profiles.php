<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('psychologist_profiles', function (Blueprint $table) {
            // Satu akun hanya boleh punya satu profil psikolog (User::psychologistProfile() adalah hasOne).
            $table->unique('user_id');

            // Satu nomor lisensi hanya boleh dipakai satu psikolog, supaya nomor valid milik
            // orang lain tidak bisa dipakai ulang untuk lolos verifikasi admin.
            // Kalau migration gagal di sini, berarti sudah ada data ganda: rapikan dulu.
            $table->unique('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('psychologist_profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropUnique(['license_number']);
        });
    }
};
