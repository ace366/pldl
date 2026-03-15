<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            // 連絡先（LINE/メール/電話）
            // ※LINEは userId を保存する想定（表示名は別途取得してもOK）
            $table->string('line_user_id', 64)->nullable()->index();
            $table->string('email', 255)->nullable()->index();
            $table->string('phone', 30)->nullable()->index();

            // 連絡の優先手段（任意）
            $table->enum('preferred_contact', ['line', 'email', 'phone'])->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
