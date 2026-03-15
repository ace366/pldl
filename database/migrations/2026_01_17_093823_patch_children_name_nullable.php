<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // name が残っていて NOT NULL だと今回の登録が必ず死ぬので nullable 化する
        if (Schema::hasColumn('children', 'name')) {
            DB::statement("ALTER TABLE `children` MODIFY `name` VARCHAR(255) NULL");
        }

        // ついでに name を自動埋め（既存データやこれからの検索に便利）
        // last_name/first_name がある行は name を補完
        DB::statement("
            UPDATE `children`
            SET `name` = TRIM(CONCAT(IFNULL(`last_name`, ''), ' ', IFNULL(`first_name`, '')))
            WHERE (`name` IS NULL OR `name` = '')
              AND (`last_name` IS NOT NULL OR `first_name` IS NOT NULL)
        ");
    }

    public function down(): void
    {
        // down は安全側で何もしない
    }
};
