<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ✅ 既にあれば何もしない
        if (!Schema::hasTable('family_messages')) return;
        if (Schema::hasColumn('family_messages', 'created_by')) return;

        Schema::table('family_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('child_id');
            $table->index('created_by');
            // users にFKしたいなら下もOK（運用次第）
            // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // ✅ 本番事故防止で drop しない
    }
};
