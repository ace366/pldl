<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 追加（無ければ足す）
        Schema::table('children', function (Blueprint $table) {
            if (!Schema::hasColumn('children', 'last_name')) {
                $table->string('last_name', 50)->after('id');
            }
            if (!Schema::hasColumn('children', 'first_name')) {
                $table->string('first_name', 50)->after('last_name');
            }
            if (!Schema::hasColumn('children', 'last_name_kana')) {
                $table->string('last_name_kana', 80)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('children', 'first_name_kana')) {
                $table->string('first_name_kana', 80)->nullable()->after('last_name_kana');
            }
            if (!Schema::hasColumn('children', 'grade')) {
                $table->unsignedTinyInteger('grade')->after('first_name_kana'); // 1〜6想定
            }
            if (!Schema::hasColumn('children', 'base_id')) {
                $table->unsignedBigInteger('base_id')->nullable()->after('grade');
            }
            if (!Schema::hasColumn('children', 'status')) {
                $table->string('status', 20)->default('enrolled')->after('base_id');
            }
            if (!Schema::hasColumn('children', 'note')) {
                $table->text('note')->nullable()->after('status');
            }

            // timestamps が無い可能性もあるので保険
            if (!Schema::hasColumn('children', 'created_at') && !Schema::hasColumn('children', 'updated_at')) {
                $table->timestamps();
            }
        });

        // status の型が狭い/ENUM違いで "enrolled" が入らず truncate している可能性が高いので強制修正
        // （doctrine/dbal なしで安全に変えられるよう raw SQL）
        DB::statement("ALTER TABLE `children` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'enrolled'");

        // base_id のFK（既にある/無いを考慮して try で保険）
        try {
            DB::statement("ALTER TABLE `children` ADD CONSTRAINT `children_base_id_foreign` FOREIGN KEY (`base_id`) REFERENCES `bases`(`id`) ON DELETE SET NULL");
        } catch (\Throwable $e) {
            // 既に存在する等は無視（本番で止めない）
        }
    }

    public function down(): void
    {
        // down は安全側で「何もしない」
        // （本番でロールバック運用しない前提ならこれが事故りにくい）
    }
};
