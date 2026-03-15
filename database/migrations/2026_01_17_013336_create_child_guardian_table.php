<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('child_guardian', function (Blueprint $table) {
            $table->id();

            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();

            // 例: 母/父/祖父母/その他（任意）
            $table->string('relation', 30)->nullable();

            $table->timestamps();

            // 同じ児童×同じ保護者の重複紐付けを防止
            $table->unique(['child_id', 'guardian_id']);
            $table->index(['guardian_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_guardian');
    }
};
