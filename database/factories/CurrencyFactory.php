<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->currencyCode(),
            'symbol' => '$',
            'rate_to_ngn' => fake()->randomFloat(4, 1, 2000),
            'is_base' => false,
            'is_active' => true,
        ];
    }
}
