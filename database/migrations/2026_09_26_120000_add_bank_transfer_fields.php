<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Direct bank transfer fallback, offered alongside the card
            // gateways when the admin has configured an account.
            $table->boolean('bank_transfer_enabled')->default(false);
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('bank_account_number')->nullable();
            // Resolved from Paystack's account-name endpoint, and stored so
            // the storefront never has to call out to Paystack to render.
            $table->string('bank_account_name')->nullable();
            $table->text('bank_transfer_note')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            // Set when the customer tells us they have sent the transfer,
            // so admins can tell "waiting on the customer" apart from
            // "customer says it's sent, go and check the bank".
            $table->timestamp('transfer_declared_at')->nullable();

            // Stock is taken the moment an order is placed (so two shoppers
            // can't both buy the last tee) and handed back if the order is
            // never paid for.
            $table->timestamp('stock_reserved_at')->nullable();
            $table->timestamp('stock_released_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bank_transfer_enabled',
                'bank_name',
                'bank_code',
                'bank_account_number',
                'bank_account_name',
                'bank_transfer_note',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['transfer_declared_at', 'stock_reserved_at', 'stock_released_at']);
        });
    }
};
