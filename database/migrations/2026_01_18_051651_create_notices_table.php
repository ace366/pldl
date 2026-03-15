<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();

            $table->string('title');              // タイトル
            $table->text('body');                 // 本文
            $table->boolean('is_active')->default(true); // 表示ON/OFF
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
