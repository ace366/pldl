<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            // 拠点
            $table->foreignId('base_id')
                ->constrained('bases')
                ->cascadeOnDelete();

            // スタッフ
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // シフト日
            $table->date('shift_date');

            // 時間帯
            $table->time('start_time');
            $table->time('end_time');

            // 状態
            $table->string('status', 20)->default('scheduled'); // scheduled/working/completed/absent/canceled(任意)

            // メモ
            $table->text('note')->nullable();

            // 作成/更新者（監査用：FKは張らずnullable）
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index（よく使う検索）
            $table->index(['base_id', 'shift_date'], 'idx_shifts_base_date');
            $table->index(['user_id', 'shift_date'], 'idx_shifts_user_date');
            $table->index(['status', 'shift_date'], 'idx_shifts_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
