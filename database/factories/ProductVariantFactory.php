<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'color' => fake()->randomElement(['Black', 'White', 'Sand']),
            'sku' => 'DC-'.strtoupper(Str::random(8)),
            'price_kobo' => null,
            'stock_quantity' => fake()->numberBetween(0, 30),
        ];
    }
}
