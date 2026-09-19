<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ganti dulu data lama yang masih 'video_call' jadi 'tatap_muka',
        // baru ubah definisi enum-nya (urutan ini penting di MySQL).
        DB::table('consultations')->where('type', 'video_call')->update(['type' => 'chat']);

        DB::statement("ALTER TABLE consultations MODIFY type ENUM('chat', 'tatap_muka') DEFAULT 'chat'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE consultations MODIFY type ENUM('chat', 'video_call') DEFAULT 'chat'");
    }
};
