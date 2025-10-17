<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('command_preset_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('command'); // e.g., 'LOCK_DEVICE', 'UNLOCK_DEVICE', etc.
            $table->string('title')->nullable();
            $table->text('message');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: one active preset message per user per command
            $table->unique(['user_id', 'command']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_preset_messages');
    }
};
