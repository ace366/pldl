<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_contact_logs', function (Blueprint $table) {
            $table->id();

            // 児童（必須）
            $table->foreignId('child_id')
                ->constrained('children')
                ->cascadeOnDelete();

            // 保護者（任意：記録時点の主担当を残したい場合など）
            $table->foreignId('guardian_id')
                ->nullable()
                ->constrained('guardians')
                ->nullOnDelete();

            // 入力者（管理者/スタッフ等の users）
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // TEL票の必須項目
            $table->string('title');                 // 必須タイトル
            $table->text('body');                    // 内容
            $table->date('contact_date')->nullable(); // 任意：対応日（未入力でもOK）

            // 将来拡張用（電話/面談/メール/家庭連絡等）
            $table->string('channel', 30)->default('tel'); // tel / visit / mail / other など

            $table->timestamps();
            $table->softDeletes();

            $table->index(['child_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_contact_logs');
    }
};
