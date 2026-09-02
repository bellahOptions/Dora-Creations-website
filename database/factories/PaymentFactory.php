<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => 'paystack',
            'reference' => 'DC-'.fake()->unique()->uuid(),
            'status' => Payment::STATUS_SUCCESSFUL,
            'amount_kobo' => fake()->numberBetween(500000, 3500000),
            'currency' => 'NGN',
            'paid_at' => now(),
        ];
    }
}
