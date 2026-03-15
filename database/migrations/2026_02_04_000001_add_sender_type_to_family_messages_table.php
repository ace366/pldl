<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('family_messages')) {
            return;
        }

        if (!Schema::hasColumn('family_messages', 'sender_type')) {
            Schema::table('family_messages', function (Blueprint $table) {
                $table->string('sender_type', 20)->default('admin')->after('created_by');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('family_messages')) {
            return;
        }

        if (Schema::hasColumn('family_messages', 'sender_type')) {
            Schema::table('family_messages', function (Blueprint $table) {
                $table->dropColumn('sender_type');
            });
        }
    }
};
