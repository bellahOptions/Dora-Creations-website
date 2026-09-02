<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\PlaceholderImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Tees', 'description' => 'Everyday graphic and plain tees, cut and printed in-house.', 'sort_order' => 1],
            ['name' => 'Tote Bags', 'description' => 'Canvas totes for the studio, the market, or the daily commute.', 'sort_order' => 2],
            ['name' => 'Hoodies & Sweatshirts', 'description' => 'Heavyweight fleece for harmattan mornings.', 'sort_order' => 3],
            ['name' => 'Accessories', 'description' => 'Caps, bucket hats and the small finishing pieces.', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            $slug = Str::slug($category['name']);

            Category::updateOrCreate(
                ['slug' => $slug],
                $category + [
                    'slug' => $slug,
                    'is_active' => true,
                    'image_path' => PlaceholderImage::store('public', "categories/{$slug}.svg", $category['name'], '#25221C'),
                ]
            );
        }
    }
}
