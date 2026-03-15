<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('family_message_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('family_message_id')->index();
            $table->unsignedBigInteger('child_id')->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['family_message_id', 'child_id']);

            $table->foreign('family_message_id')->references('id')->on('family_messages')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_message_reads');
    }
};
