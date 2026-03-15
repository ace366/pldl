<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('family_message_admin_reads')) {
            return;
        }

        Schema::create('family_message_admin_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('family_message_id');
            $table->unsignedBigInteger('child_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['family_message_id', 'child_id']);
            $table->foreign('family_message_id')->references('id')->on('family_messages')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('family_message_admin_reads')) {
            Schema::dropIfExists('family_message_admin_reads');
        }
    }
};
