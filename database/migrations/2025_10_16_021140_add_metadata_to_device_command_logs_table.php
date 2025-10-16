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
        Schema::table('device_command_logs', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('fcm_response')->comment('Response data from device (e.g., location data, command execution results)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_command_logs', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
