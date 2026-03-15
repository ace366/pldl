<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('family_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id')->index();
            $table->unsignedBigInteger('admin_user_id')->nullable()->index(); // users.id（任意）
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            // users テーブルがあるのでFK付けてもOK。怖ければ外しても可
            $table->foreign('admin_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_messages');
    }
};
