<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Doctrine(DBAL)不要のため、生SQLで対応
        // base が NOT NULL のときだけ NULL 許可へ（型は既存に合わせて調整してください）
        DB::statement("ALTER TABLE `children` MODIFY `base` VARCHAR(255) NULL");

        // もし name も NOT NULL で残っている場合の保険（以前のエラー対策）
        // 既に nullable なら実行しても問題になりにくいですが、型が違う場合は調整してください
        // DB::statement(\"ALTER TABLE `children` MODIFY `name` VARCHAR(255) NULL\");
    }

    public function down(): void
    {
        // 戻す必要があるなら NOT NULL に戻します（運用に合わせて）
        DB::statement("ALTER TABLE `children` MODIFY `base` VARCHAR(255) NOT NULL");
        // DB::statement(\"ALTER TABLE `children` MODIFY `name` VARCHAR(255) NOT NULL\");
    }
};
