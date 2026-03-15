<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            if (!Schema::hasColumn('children', 'message_icon_guardian_id')) {
                $table->unsignedBigInteger('message_icon_guardian_id')->nullable()->after('family_login_code');
                $table->index('message_icon_guardian_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            if (Schema::hasColumn('children', 'message_icon_guardian_id')) {
                $table->dropIndex(['message_icon_guardian_id']);
                $table->dropColumn('message_icon_guardian_id');
            }
        });
    }
};

