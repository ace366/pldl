<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_closings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('base_id')
                ->constrained('bases')
                ->cascadeOnDelete();

            // YYYY-MM 形式（例: 2026-02）
            $table->char('year_month', 7);

            $table->dateTime('closed_at');
            $table->unsignedBigInteger('closed_by'); // users.id（FKなし）

            $table->text('note')->nullable();

            // 将来：締め解除
            $table->dateTime('reopened_at')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable(); // users.id（FKなし）

            $table->timestamps();

            // 拠点×年月はユニーク
            $table->unique(['base_id', 'year_month'], 'uq_ac_base_month');

            $table->index(['year_month'], 'idx_ac_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_closings');
    }
};
