<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Urutan penting: hapus dulu ai_chat_messages (yang punya FK ke ai_chat_sessions),
        // baru ai_chat_sessions -- supaya tidak error foreign key constraint.
        Schema::dropIfExists('ai_chat_messages');
        Schema::dropIfExists('ai_chat_sessions');
    }

    public function down(): void
    {
        // Sengaja dikosongkan -- fitur AI Chat tidak jadi dipakai,
        // jadi tidak perlu bisa di-rollback untuk membuat ulang tabelnya.
    }
};
