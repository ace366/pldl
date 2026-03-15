<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            // どのシフト/勤怠に紐づくか（将来の例外も見て NULL 許容）
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('shifts')
                ->nullOnDelete();

            $table->foreignId('shift_attendance_id')
                ->nullable()
                ->constrained('shift_attendances')
                ->nullOnDelete();

            // 対象者（勤怠の本人）
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // 対象拠点
            $table->foreignId('base_id')
                ->constrained('bases')
                ->cascadeOnDelete();

            // イベント種別
            $table->string('action', 30); // clock_in / clock_out / admin_edit / ...

            // 打刻経路
            $table->string('source', 20)->default('web'); // web / qr / admin / api(将来)

            // 発生時刻
            $table->dateTime('occurred_at');

            // 監査情報
            $table->string('ip_address', 45)->nullable(); // IPv6対応
            $table->text('user_agent')->nullable();

            // before/after、理由、補足などをJSONで
            $table->json('payload')->nullable();

            // 実行者（本人 or 管理者）
            $table->unsignedBigInteger('actor_user_id')->nullable(); // users.id（FKなしでもOK）

            $table->timestamps();

            // Index（監査・集計に効く）
            $table->index(['base_id', 'occurred_at'], 'idx_al_base_time');
            $table->index(['user_id', 'occurred_at'], 'idx_al_user_time');
            $table->index(['shift_attendance_id', 'occurred_at'], 'idx_al_sa_time');
            $table->index(['action', 'occurred_at'], 'idx_al_action_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
