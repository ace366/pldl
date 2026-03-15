<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_attendances', function (Blueprint $table) {
            $table->id();

            // 1シフト1勤怠（UNIQUEで担保）
            $table->foreignId('shift_id')
                ->constrained('shifts')
                ->cascadeOnDelete();

            // 検索最適化のため冗長に保持（JOIN減らす）
            $table->foreignId('base_id')
                ->constrained('bases')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // 基準日（原則 shift_date と同じ。日跨ぎでも基準として固定）
            $table->date('attendance_date');

            // 打刻
            $table->dateTime('clock_in_at')->nullable();
            $table->dateTime('clock_out_at')->nullable();

            // 休憩（将来拡張に備えて分ける）
            $table->unsignedInteger('break_minutes')->default(0);       // 手動入力（将来）
            $table->unsignedInteger('auto_break_minutes')->default(0);  // 自動控除結果

            // 集計用（サーバ側で一元計算して保存）
            $table->unsignedInteger('work_minutes')->default(0);

            // 状態
            $table->string('status', 20)->default('scheduled'); // scheduled/working/completed/absent

            // 締めロック（物理フラグ：運用を堅く）
            $table->boolean('is_locked')->default(false);
            $table->dateTime('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable(); // users.id（FKなし）

            // メモ（遅刻理由など）
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 1シフト1勤怠
            $table->unique(['shift_id'], 'uq_shift_attendances_shift');

            // Index（検索頻出）
            $table->index(['base_id', 'attendance_date'], 'idx_sa_base_date');
            $table->index(['user_id', 'attendance_date'], 'idx_sa_user_date');
            $table->index(['status', 'attendance_date'], 'idx_sa_status_date');
            $table->index(['clock_in_at'], 'idx_sa_clock_in_at');
            $table->index(['clock_out_at'], 'idx_sa_clock_out_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_attendances');
    }
};
