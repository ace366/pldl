<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 既にカラムがある環境でも落ちないようにガード
        if (!Schema::hasColumn('child_guardian', 'relationship')) {
            Schema::table('child_guardian', function (Blueprint $table) {
                $table->string('relationship', 30)->nullable()->after('guardian_id');
            });
        }
    }

    public function down(): void
    {
        // rollback できるように（存在チェックして安全に）
        if (Schema::hasColumn('child_guardian', 'relationship')) {
            Schema::table('child_guardian', function (Blueprint $table) {
                $table->dropColumn('relationship');
            });
        }
    }
};
