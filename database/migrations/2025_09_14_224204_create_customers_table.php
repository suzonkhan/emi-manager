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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nid_no', 100)->unique(); // National ID number - limited for composite index
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile', 100); // Limited for composite index

            // Present Address
            $table->foreignId('present_address_id')->constrained('addresses')->onDelete('cascade');

            // Permanent Address
            $table->foreignId('permanent_address_id')->constrained('addresses')->onDelete('cascade');

            // Product Information
            $table->foreignId('token_id')->constrained('tokens')->onDelete('cascade');
            $table->integer('emi_duration_months'); // EMI duration in months
            $table->string('product_type');
            $table->string('product_model')->nullable();
            $table->decimal('product_price', 10, 2);
            $table->decimal('down_payment', 10, 2)->default(0);
            $table->decimal('emi_per_month', 8, 2);
            $table->string('imei_1')->nullable();
            $table->string('imei_2')->nullable();

            // Device Information
            $table->string('serial_number')->nullable()->unique();
            $table->text('fcm_token')->nullable();

            // Device Control States (boolean fields for device features)
            $table->boolean('is_device_locked')->default(false);
            $table->boolean('is_camera_disabled')->default(false);
            $table->boolean('is_bluetooth_disabled')->default(false);
            $table->boolean('is_app_hidden')->default(false);
            $table->boolean('has_password')->default(false);
            $table->string('custom_wallpaper_url')->nullable();
            $table->timestamp('last_command_sent_at')->nullable();

            // Who created this customer
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Dealer tracking - which dealer this customer belongs to
            $table->foreignId('dealer_id')->nullable()->constrained('users')->onDelete('cascade');

            // Sequential customer number per dealer (1, 2, 3... for each dealer)
            $table->unsignedInteger('dealer_customer_id')->nullable();

            // Document Archive
            $table->json('documents')->nullable(); // Store uploaded document paths

            $table->enum('status', ['active', 'completed', 'defaulted', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['nid_no', 'mobile']); // Now safe with limited lengths
            $table->index(['created_by', 'status']);
            $table->index('token_id');
            $table->index('dealer_customer_id');

            // Ensure no duplicate customer IDs per dealer
            $table->unique(['dealer_id', 'dealer_customer_id'], 'unique_dealer_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
