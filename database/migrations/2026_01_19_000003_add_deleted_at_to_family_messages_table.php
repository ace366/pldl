<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('family_messages')) return;
        if (Schema::hasColumn('family_messages', 'deleted_at')) return;

        Schema::table('family_messages', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        // 本番事故防止で戻さない
        // Schema::table('family_messages', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
