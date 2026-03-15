<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('child_guardian')) return;

        Schema::create('child_guardian', function (Blueprint $table) {
            $table->id();

            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();

            // 続柄（任意：母/父/祖父母など）
            $table->string('relationship', 30)->nullable();

            $table->timestamps();

            $table->unique(['child_id', 'guardian_id']);
            $table->index(['guardian_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_guardian');
    }
};
