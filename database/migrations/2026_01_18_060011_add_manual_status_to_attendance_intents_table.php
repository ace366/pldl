<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('child_attendance_intents', function (Blueprint $table) {
            // ✅ after() は使わない（存在しないカラム指定で落ちるため）
            if (!Schema::hasColumn('child_attendance_intents', 'manual_status')) {
                $table->string('manual_status', 20)->nullable();
            }
            if (!Schema::hasColumn('child_attendance_intents', 'manual_updated_at')) {
                $table->timestamp('manual_updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('child_attendance_intents', function (Blueprint $table) {
            if (Schema::hasColumn('child_attendance_intents', 'manual_updated_at')) {
                $table->dropColumn('manual_updated_at');
            }
            if (Schema::hasColumn('child_attendance_intents', 'manual_status')) {
                $table->dropColumn('manual_status');
            }
        });
    }
};
