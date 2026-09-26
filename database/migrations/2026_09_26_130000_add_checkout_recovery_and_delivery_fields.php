<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Which cart this order came from. The cart is only emptied once
            // payment succeeds, so the shopper can go back and edit if they
            // abandoned the gateway page — and this is how the webhook (which
            // has no session) still knows which cart to empty.
            $table->unsignedBigInteger('cart_id')->nullable()->index();

            // Set when the order includes an item whose size/colour the
            // customer couldn't choose because none were configured. The
            // studio confirms it afterwards, so delivery takes longer.
            $table->boolean('needs_variant_confirmation')->default(false);

            $table->timestamp('estimated_delivery_at')->nullable();
        });

        Schema::table('site_settings', function (Blueprint $table) {
            // Standard delivery window, before any per-order adjustments.
            $table->unsignedSmallInteger('delivery_lead_days')->default(3);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cart_id', 'needs_variant_confirmation', 'estimated_delivery_at']);
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('delivery_lead_days');
        });
    }
};
