<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // すでに存在するなら何もしない（本番DBを壊さない）
        if (Schema::hasTable('schools')) {
            return;
        }

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // 既存運用データを誤って消さないため、ここは dropIfExists のままでOK
        Schema::dropIfExists('schools');
    }
};
