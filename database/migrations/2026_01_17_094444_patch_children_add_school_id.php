<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            // 既にある場合に備えて hasColumn チェック
            if (!Schema::hasColumn('children', 'school_id')) {
                // 既存データがある可能性を考慮して nullable（後で必須化も可能）
                $table->unsignedBigInteger('school_id')->nullable()->after('base_id');
                $table->index('school_id', 'children_school_id_index');
            }
        });

        // FKは別で追加（環境差で落ちにくくする）
        // ※ schools テーブルのPKは id 前提
        Schema::table('children', function (Blueprint $table) {
            // 外部キー名を固定（重複しにくい）
            // 既にFKがある環境では migrate が落ちる可能性があるため try/catch 的なことは Schema では難しい
            // ここは「初回追加」前提で進めます
            if (Schema::hasColumn('children', 'school_id')) {
                $table->foreign('school_id', 'children_school_id_foreign')
                    ->references('id')->on('schools')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            // 外部キー→index→column の順で落とす
            try { $table->dropForeign('children_school_id_foreign'); } catch (\Throwable $e) {}
            try { $table->dropIndex('children_school_id_index'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('children', 'school_id')) {
                $table->dropColumn('school_id');
            }
        });
    }
};
