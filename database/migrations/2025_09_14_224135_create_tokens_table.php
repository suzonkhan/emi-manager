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
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique(); // 12-character token code
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Who generated this token
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Current holder
            $table->enum('status', ['available', 'assigned', 'used'])->default('available');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->json('metadata')->nullable(); // Store additional info like assignment chain
            $table->timestamps();

            $table->index(['status', 'assigned_to']);
            $table->index(['created_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
