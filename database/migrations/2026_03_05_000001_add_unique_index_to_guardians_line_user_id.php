<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guardians') || !Schema::hasColumn('guardians', 'line_user_id')) {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM guardians'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (in_array('guardians_line_user_id_unique', $indexes, true)) {
            return;
        }

        $duplicateCount = DB::table('guardians')
            ->select('line_user_id')
            ->whereNotNull('line_user_id')
            ->where('line_user_id', '<>', '')
            ->groupBy('line_user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateCount > 0) {
            throw new RuntimeException('guardians.line_user_id に重複データが存在するため unique 制約を追加できません。');
        }

        if (in_array('guardians_line_user_id_index', $indexes, true)) {
            Schema::table('guardians', function (Blueprint $table) {
                $table->dropIndex('guardians_line_user_id_index');
            });
        }

        Schema::table('guardians', function (Blueprint $table) {
            $table->unique('line_user_id', 'guardians_line_user_id_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guardians') || !Schema::hasColumn('guardians', 'line_user_id')) {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM guardians'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (in_array('guardians_line_user_id_unique', $indexes, true)) {
            Schema::table('guardians', function (Blueprint $table) {
                $table->dropUnique('guardians_line_user_id_unique');
            });
        }

        if (!in_array('guardians_line_user_id_index', $indexes, true)) {
            Schema::table('guardians', function (Blueprint $table) {
                $table->index('line_user_id', 'guardians_line_user_id_index');
            });
        }
    }
};
