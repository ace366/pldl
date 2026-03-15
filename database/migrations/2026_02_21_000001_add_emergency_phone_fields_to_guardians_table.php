<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            if (!Schema::hasColumn('guardians', 'emergency_phone')) {
                $table->string('emergency_phone', 30)->nullable()->after('phone');
                $table->index('emergency_phone');
            }

            if (!Schema::hasColumn('guardians', 'emergency_phone_label')) {
                $table->string('emergency_phone_label', 80)->nullable()->after('emergency_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            if (Schema::hasColumn('guardians', 'emergency_phone_label')) {
                $table->dropColumn('emergency_phone_label');
            }
            if (Schema::hasColumn('guardians', 'emergency_phone')) {
                $table->dropIndex(['emergency_phone']);
                $table->dropColumn('emergency_phone');
            }
        });
    }
};
