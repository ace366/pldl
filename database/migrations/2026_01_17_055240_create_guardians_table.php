<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('guardians')) return;

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();

            // 氏名（漢字）
            $table->string('last_name', 50);
            $table->string('first_name', 50);

            // ふりがな
            $table->string('last_name_kana', 50)->nullable();
            $table->string('first_name_kana', 50)->nullable();

            // 連絡先（複数持てる形）
            $table->string('email', 255)->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('line_user_id', 100)->nullable()->index();

            // 優先連絡手段
            $table->enum('preferred_contact', ['line', 'email', 'phone'])->nullable()->index();

            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
