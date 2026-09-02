<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(8000, 60000) * 100;
        $shipping = 250000;

        return [
            'user_id' => User::factory(),
            'guest_email' => null,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'display_currency' => 'NGN',
            'subtotal_kobo' => $subtotal,
            'shipping_kobo' => $shipping,
            'total_kobo' => $subtotal + $shipping,
            'shipping_full_name' => fake()->name(),
            'shipping_phone' => '+234'.fake()->numerify('##########'),
            'shipping_country' => 'Nigeria',
            'shipping_state' => fake()->randomElement(['Lagos', 'Abuja', 'Rivers']),
            'shipping_city' => fake()->city(),
            'shipping_line1' => fake()->streetAddress(),
            'shipping_line2' => null,
            'shipping_postal_code' => fake()->postcode(),
            'payment_gateway' => null,
            'payment_reference' => null,
            'paid_at' => null,
            'customer_note' => null,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'guest_email' => fake()->safeEmail(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => Order::STATUS_PROCESSING,
            'payment_gateway' => 'paystack',
            'payment_reference' => 'DC-'.fake()->unique()->uuid(),
            'paid_at' => now(),
        ]);
    }
}
