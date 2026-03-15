<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();

            // 氏名（検索性・表示を優先して1カラム）
            $table->string('name', 100);

            // 学年（例: 1,2,3... / あるいは "年長" 等の運用も想定して文字列）
            $table->string('grade', 20)->index();

            // 拠点（将来拠点テーブル化しても良いが、まずは文字列）
            $table->string('base', 100)->index();

            // 状態（在籍/退会）
            $table->enum('status', ['active', 'inactive'])->default('active')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
