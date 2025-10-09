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
        Schema::table('customers', function (Blueprint $table) {
            // Add dealer_id to track which dealer this customer belongs to
            $table->foreignId('dealer_id')->nullable()->after('created_by')->constrained('users')->onDelete('cascade');

            // Add dealer_customer_id - sequential customer number per dealer
            // For dealer X: customers will be numbered 1, 2, 3, 4...
            // For dealer Y: customers will also be numbered 1, 2, 3, 4...
            $table->unsignedInteger('dealer_customer_id')->nullable()->after('dealer_id');

            // Create composite unique index to ensure no duplicate customer IDs per dealer
            $table->unique(['dealer_id', 'dealer_customer_id'], 'unique_dealer_customer');

            // Index for faster queries
            $table->index('dealer_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('unique_dealer_customer');
            $table->dropForeign(['dealer_id']);
            $table->dropColumn(['dealer_id', 'dealer_customer_id']);
        });
    }
};
