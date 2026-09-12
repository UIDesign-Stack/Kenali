<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->date('birth_date')->nullable()->after('phone');
            $table->enum('gender', ['laki-laki', 'perempuan'])->nullable()->after('birth_date');
            $table->enum('life_phase', ['siswa', 'mahasiswa', 'pekerja'])->nullable()->after('gender');
            $table->string('avatar')->nullable()->after('life_phase');
            $table->boolean('is_active')->default(true)->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'birth_date', 'gender', 'life_phase', 'avatar', 'is_active']);
        });
    }
};
