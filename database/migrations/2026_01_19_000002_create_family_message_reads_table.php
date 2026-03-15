<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function tableExists(string $table): bool
    {
        $db = DB::connection()->getDatabaseName();

        $row = DB::selectOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.tables
             WHERE table_schema = ? AND table_name = ?",
            [$db, $table]
        );

        return ((int)($row->c ?? 0)) > 0;
    }

    public function up(): void
    {
        // ✅ 既にあるなら終了（Schema::hasTable の誤判定を回避）
        if ($this->tableExists('family_message_reads')) {
            return;
        }

        Schema::create('family_message_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('child_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'child_id']);
            $table->index(['child_id', 'read_at']);

            $table->foreign('message_id')->references('id')->on('family_messages')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // 本番事故防止：dropしない
        // Schema::dropIfExists('family_message_reads');
    }
};
