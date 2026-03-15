<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('schools')) {
            // 万一無い環境でも安全に
            Schema::create('schools', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            // name が無いなら追加
            if (!Schema::hasColumn('schools', 'name')) {
                $table->string('name', 150)->nullable()->after('id');
            }

            // is_active が無いなら追加
            if (!Schema::hasColumn('schools', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name')->index();
            }

            // created_at/updated_at が無いなら追加（無ければ）
            if (!Schema::hasColumn('schools', 'created_at') && !Schema::hasColumn('schools', 'updated_at')) {
                $table->timestamps();
            }
        });

        /**
         * ★ここが重要：既存の学校名カラムが別名なら、name に移植する
         * ありがちな候補：school_name / school / title / schoolName
         * 実際のカラム名は tinker の出力に合わせて1行だけ生かす
         */
        if (Schema::hasColumn('schools', 'school_name')) {
            DB::statement("UPDATE schools SET name = COALESCE(name, school_name)");
        } elseif (Schema::hasColumn('schools', 'school')) {
            DB::statement("UPDATE schools SET name = COALESCE(name, school)");
        } elseif (Schema::hasColumn('schools', 'title')) {
            DB::statement("UPDATE schools SET name = COALESCE(name, title)");
        } elseif (Schema::hasColumn('schools', 'schoolName')) {
            DB::statement("UPDATE schools SET name = COALESCE(name, schoolName)");
        }

        // name を NOT NULL & UNIQUE に寄せたいが、既存データ状況が不明なのでここでは強制しない
        // まず運用を通し、後で整備（重複やNULLを解消）するのが安全。
    }

    public function down(): void
    {
        // 既存運用に影響しやすいので down は触らない（ロールバックで消さない）
        // 必要なら個別に戻しマイグレーションを作る
    }
};
