<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ✅ すでに存在するなら何もしない（事故防止）
        if (Schema::hasTable('family_messages')) {
            return;
        }

        Schema::create('family_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('title')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['child_id', 'created_at']);
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // ✅ down は何もしない（既存テーブルを消す事故防止）
        // Schema::dropIfExists('family_messages');
    }
};
