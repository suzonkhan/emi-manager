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
        Schema::create('device_location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 10, 2)->nullable(); // in meters
            $table->decimal('altitude', 10, 2)->nullable(); // in meters
            $table->decimal('speed', 10, 2)->nullable(); // in m/s
            $table->string('provider')->nullable(); // GPS, Network, etc.
            $table->string('address')->nullable(); // Human-readable address (if reverse geocoded)
            $table->json('metadata')->nullable(); // Any additional data from device
            $table->timestamp('recorded_at'); // When device recorded the location
            $table->timestamps();
            
            // Indexes
            $table->index('customer_id');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_location_logs');
    }
};
