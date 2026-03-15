<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 氏名（漢字）
            $table->string('last_name', 50)->nullable()->after('id');
            $table->string('first_name', 50)->nullable()->after('last_name');

            // ふりがな
            $table->string('last_name_kana', 50)->nullable()->after('first_name');
            $table->string('first_name_kana', 50)->nullable()->after('last_name_kana');

            // 学校（マスタ）
            $table->foreignId('school_id')->nullable()->after('email')->constrained('schools')->nullOnDelete();

            // 電話（数字のみ保存）
            $table->string('phone', 20)->nullable()->after('school_id');

            // 学年（1〜6）
            $table->unsignedTinyInteger('grade')->nullable()->after('phone');

            // 備考（配慮事項など）
            $table->text('note')->nullable()->after('grade');

            // 管理者判定用（まずはシンプルに）
            $table->boolean('is_admin')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');

            $table->dropColumn([
                'last_name',
                'first_name',
                'last_name_kana',
                'first_name_kana',
                'phone',
                'grade',
                'note',
                'is_admin',
            ]);
        });
    }
};
