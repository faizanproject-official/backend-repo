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
        Schema::table('transactions', function (Blueprint $table) {
            // Stores the Stripe PaymentIntent ID or unique transfer ID to prevent double-spending
            $table->string('transaction_reference')->nullable()->unique()->after('status');
            
            // Stores 'stripe', 'system', 'manual'
            $table->string('payment_gateway')->default('system')->after('type');
            
            // Add index for faster lookups
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['transaction_reference', 'payment_gateway']);
        });
    }
};
