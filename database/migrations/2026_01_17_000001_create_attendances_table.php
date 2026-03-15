<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // 子どもマスタを使う設計（children.id）
            $table->unsignedBigInteger('child_id');

            // 誰がスキャンしたか（任意）
            $table->unsignedBigInteger('scanned_by')->nullable();

            // 種別（必要なら拡張）
            $table->string('attendance_type', 20)->default('in'); // in/out など

            $table->timestamp('attended_at')->useCurrent();
            $table->timestamps();

            $table->index(['child_id', 'attended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
