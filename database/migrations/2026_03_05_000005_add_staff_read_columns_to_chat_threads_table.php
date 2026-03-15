<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chat_threads')) {
            return;
        }

        Schema::table('chat_threads', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_threads', 'unread_count_staff')) {
                $table->unsignedInteger('unread_count_staff')->default(0)->after('last_message_at');
                $table->index('unread_count_staff');
            }

            if (!Schema::hasColumn('chat_threads', 'last_staff_read_at')) {
                $table->timestamp('last_staff_read_at')->nullable()->after('unread_count_staff');
                $table->index('last_staff_read_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chat_threads')) {
            return;
        }

        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'last_staff_read_at')) {
                $table->dropIndex(['last_staff_read_at']);
                $table->dropColumn('last_staff_read_at');
            }
            if (Schema::hasColumn('chat_threads', 'unread_count_staff')) {
                $table->dropIndex(['unread_count_staff']);
                $table->dropColumn('unread_count_staff');
            }
        });
    }
};
