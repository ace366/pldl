<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            if (!Schema::hasColumn('guardians', 'last_name')) {
                $table->string('last_name', 50)->after('id');
            }
            if (!Schema::hasColumn('guardians', 'first_name')) {
                $table->string('first_name', 50)->after('last_name');
            }
            if (!Schema::hasColumn('guardians', 'last_name_kana')) {
                $table->string('last_name_kana', 50)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('guardians', 'first_name_kana')) {
                $table->string('first_name_kana', 50)->nullable()->after('last_name_kana');
            }
            if (!Schema::hasColumn('guardians', 'email')) {
                $table->string('email', 255)->nullable()->after('first_name_kana');
                $table->index('email');
            }
            if (!Schema::hasColumn('guardians', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
                $table->index('phone');
            }
            if (!Schema::hasColumn('guardians', 'line_user_id')) {
                $table->string('line_user_id', 100)->nullable()->after('phone');
                $table->index('line_user_id');
            }
            if (!Schema::hasColumn('guardians', 'preferred_contact')) {
                $table->enum('preferred_contact', ['email', 'phone', 'line'])
                      ->default('phone')
                      ->after('line_user_id');
            }

            // 既にtimestampsが無い場合だけ追加
            if (!Schema::hasColumn('guardians', 'created_at') && !Schema::hasColumn('guardians', 'updated_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            // down は運用次第。安全側で何もしないでもOK
        });
    }
};
