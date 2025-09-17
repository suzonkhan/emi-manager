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
            $table->decimal('emi_per_month', 8, 2);
            $table->string('imei_1')->nullable();
            $table->string('imei_2')->nullable();

            // Who created this customer
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Document Archive
            $table->json('documents')->nullable(); // Store uploaded document paths

            $table->enum('status', ['active', 'completed', 'defaulted', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['nid_no', 'mobile']); // Now safe with limited lengths
            $table->index(['created_by', 'status']);
            $table->index('token_id');
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
