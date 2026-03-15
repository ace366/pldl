<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_attendance_intents', function (Blueprint $table) {
            $table->id();

            // 児童（childrenテーブル）に紐づく
            $table->unsignedBigInteger('child_id');

            // 参加予定日
            $table->date('date');

            // 将来：送迎の要否（ステップ2でUI追加しやすいように先に用意）
            $table->boolean('pickup_required')->default(false);

            $table->timestamps();

            // 1児童×1日をユニーク
            $table->unique(['child_id', 'date']);

            // children が存在する前提。もしテーブル名が違うなら教えてください（すぐ合わせます）
            $table->foreign('child_id')
                  ->references('id')
                  ->on('children')
                  ->onDelete('cascade');

            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_attendance_intents');
    }
};
