<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('chat_threads')->cascadeOnDelete();
            $table->enum('sender_type', ['family', 'staff', 'system']);
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('line_message_id', 191)->nullable();
            $table->enum('delivery_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
            $table->index('line_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
