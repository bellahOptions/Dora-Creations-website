<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('public_token', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable();

            $table->string('status')->default('pending_payment');

            $table->string('display_currency', 3)->default('NGN');
            $table->unsignedBigInteger('subtotal_kobo');
            $table->unsignedBigInteger('shipping_kobo')->default(0);
            $table->unsignedBigInteger('total_kobo');

            $table->string('shipping_full_name');
            $table->string('shipping_phone');
            $table->string('shipping_country')->default('Nigeria');
            $table->string('shipping_state');
            $table->string('shipping_city');
            $table->string('shipping_line1');
            $table->string('shipping_line2')->nullable();
            $table->string('shipping_postal_code')->nullable();

            $table->string('payment_gateway')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('customer_note')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
