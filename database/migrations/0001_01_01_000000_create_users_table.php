<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use function Laravel\Prompts\table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('phone_number')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency', 5)->default('MYR');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string(column: 'type'); //'credit', 'debit'
            $table->string('status')->default('pending'); //'pending', 'completed', 'failed'
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
