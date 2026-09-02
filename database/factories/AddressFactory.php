<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Home',
            'full_name' => fake()->name(),
            'phone' => '+234'.fake()->numerify('##########'),
            'country' => 'Nigeria',
            'state' => fake()->randomElement(['Lagos', 'Abuja', 'Rivers', 'Oyo', 'Kano']),
            'city' => fake()->city(),
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'postal_code' => fake()->postcode(),
            'is_default' => true,
        ];
    }
}
