<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('sent_at');
            $table->index(['consultation_id', 'sender_id', 'read_at'], 'cm_unread_lookup');
        });

        DB::table('consultation_messages')->update(['read_at' => DB::raw('sent_at')]);
    }

    public function down(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->dropIndex('cm_unread_lookup');
            $table->dropColumn('read_at');
        });
    }
};