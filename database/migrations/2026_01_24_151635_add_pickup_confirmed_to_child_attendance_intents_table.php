<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_attendance_intents', function (Blueprint $table) {
            $table->boolean('pickup_confirmed')->default(false)->after('pickup_required');
            $table->timestamp('pickup_confirmed_at')->nullable()->after('pickup_confirmed');
        });
    }

    public function down(): void
    {
        Schema::table('child_attendance_intents', function (Blueprint $table) {
            $table->dropColumn(['pickup_confirmed', 'pickup_confirmed_at']);
        });
    }
};
