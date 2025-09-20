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
        Schema::table('users', function (Blueprint $table) {
            // Drop the wrong column
            $table->dropColumn('phone_number');

            // Re-add correct column type
            $table->string('phone_number', 20)->unique()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rollback: drop string and re-add timestamp (original mistake)
            $table->dropColumn('phone_number');
            $table->timestamp('phone_number')->unique()->nullable();
        });
    }
};
