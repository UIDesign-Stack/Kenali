<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Dijalankan bersamaan dengan aktifnya `implements MustVerifyEmail` pada model User.
     *
     * Sebelum itu, middleware 'verified' tidak memeriksa apa pun, jadi semua akun yang ada
     * sudah punya akses penuh. Migration ini mempertahankan akses itu: akun lama tidak
     * dipaksa verifikasi ulang (dan tidak terkunci). Hanya pendaftar baru setelah rilis ini
     * yang wajib memverifikasi email.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Tidak bisa dibatalkan: tidak ada cara membedakan akun yang dulu kosong
        // dari yang memang sudah terverifikasi.
    }
};
