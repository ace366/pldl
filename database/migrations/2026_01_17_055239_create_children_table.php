<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('children')) return;

        Schema::create('children', function (Blueprint $table) {
            $table->id();

            // 氏名（漢字）
            $table->string('last_name', 50);
            $table->string('first_name', 50);

            // ふりがな
            $table->string('last_name_kana', 50)->nullable();
            $table->string('first_name_kana', 50)->nullable();

            // 学年（1〜6）
            $table->unsignedTinyInteger('grade')->index();

            // 拠点
            $table->foreignId('base_id')->nullable()->constrained('bases')->nullOnDelete();

            // 状態（在籍/退会）
            $table->enum('status', ['enrolled', 'withdrawn'])->default('enrolled')->index();

            // 任意メモ（児童に配慮が必要な情報など：登録フォームの備考を将来ここに移してもOK）
            $table->text('note')->nullable();

            $table->timestamps();

            // よく使う検索用（氏名）
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
