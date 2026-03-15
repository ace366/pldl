<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {

            // -------------------------
            // 生年月日
            // -------------------------
            if (!Schema::hasColumn('children', 'birth_date')) {
                $table->date('birth_date')
                      ->nullable()
                      ->after('first_name_kana')
                      ->comment('生年月日');
            }

            // -------------------------
            // アレルギー有無
            // -------------------------
            if (!Schema::hasColumn('children', 'has_allergy')) {
                $table->boolean('has_allergy')
                      ->default(0)
                      ->after('birth_date')
                      ->comment('アレルギー有無（0:無 1:有）');
            }

            // -------------------------
            // アレルギー内容
            // -------------------------
            if (!Schema::hasColumn('children', 'allergy_note')) {
                $table->text('allergy_note')
                      ->nullable()
                      ->after('has_allergy')
                      ->comment('アレルギー内容');
            }
        });

        // -------------------------
        // FULLTEXT インデックス
        // ※ Laravel Schema では複数列 FULLTEXT が弱いので生SQL
        // -------------------------
        DB::statement("
            ALTER TABLE `children`
            ADD FULLTEXT INDEX `children_fulltext_idx` (
                `child_code`,
                `last_name`,
                `first_name`,
                `last_name_kana`,
                `first_name_kana`,
                `name`,
                `base`,
                `status`,
                `note`
            )
        ");
    }

    public function down(): void
    {
        // FULLTEXT削除
        DB::statement("
            ALTER TABLE `children`
            DROP INDEX `children_fulltext_idx`
        ");

        Schema::table('children', function (Blueprint $table) {
            if (Schema::hasColumn('children', 'allergy_note')) {
                $table->dropColumn('allergy_note');
            }
            if (Schema::hasColumn('children', 'has_allergy')) {
                $table->dropColumn('has_allergy');
            }
            if (Schema::hasColumn('children', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
        });
    }
};
