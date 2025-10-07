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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->integer('installment_number'); // 1, 2, 3, etc.
            $table->decimal('amount', 8, 2); // Installment amount
            $table->date('due_date'); // When payment is due
            $table->date('paid_date')->nullable(); // When payment was made
            $table->decimal('paid_amount', 8, 2)->default(0); // Amount actually paid
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'waived'])->default('pending');
            $table->text('notes')->nullable(); // Optional notes for this installment
            $table->foreignId('collected_by')->nullable()->constrained('users')->onDelete('set null'); // Who collected the payment
            $table->string('payment_method')->nullable(); // cash, bank transfer, mobile banking, etc.
            $table->string('transaction_reference')->nullable(); // Transaction ID or reference number
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('due_date');
            $table->unique(['customer_id', 'installment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
