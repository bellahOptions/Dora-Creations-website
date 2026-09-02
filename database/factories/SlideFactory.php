<?php

namespace Database\Factories;

use App\Models\Slide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slide>
 */
class SlideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'headline' => fake()->unique()->sentence(3),
            'subheadline' => fake()->sentence(),
            'image_path' => 'https://via.placeholder.com/1200x800',
            'cta_label' => 'Shop now',
            'cta_url' => '/shop',
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
