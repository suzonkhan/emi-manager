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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Parent-child relationship for nested structure
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('cascade');

            // Address fields with foreign key constraints
            $table->foreignId('present_address_id')->nullable()->constrained('addresses')->onDelete('set null');
            $table->foreignId('permanent_address_id')->nullable()->constrained('addresses')->onDelete('set null');

            // Payment merchant numbers
            $table->string('bkash_merchant_number')->nullable();
            $table->string('nagad_merchant_number')->nullable();

            // Password change permission flag
            $table->boolean('can_change_password')->default(false);

            // Status and metadata
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->json('metadata')->nullable(); // For storing additional user data

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
