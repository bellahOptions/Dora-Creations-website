<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Support\PlaceholderImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Demo catalog standing in for Dora's real photography/copy until it's
     * added through the admin Products CRUD.
     */
    public function run(): void
    {
        $palette = ['#1B1913', '#A94A17', '#175641', '#4A4438', '#833914'];

        $products = [
            ['category' => 'Tees', 'name' => 'Adire Waves Tee', 'price' => 12500, 'featured' => true, 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Black', 'White', 'Sand']],
            ['category' => 'Tees', 'name' => 'Naija Heritage Graphic Tee', 'price' => 13000, 'featured' => true, 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Black', 'White']],
            ['category' => 'Tees', 'name' => 'Lagos Skyline Tee', 'price' => 12000, 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Black', 'Forest']],
            ['category' => 'Tees', 'name' => 'Minimal Logo Tee', 'price' => 9500, 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['White', 'Black']],
            ['category' => 'Tees', 'name' => 'Ankara Trim Tee', 'price' => 14500, 'featured' => true, 'sizes' => ['S', 'M', 'L'], 'colors' => ['Sand']],
            ['category' => 'Tees', 'name' => 'Ochre Sunset Tee', 'price' => 12500, 'sizes' => ['M', 'L', 'XL'], 'colors' => ['Ochre']],
            ['category' => 'Tote Bags', 'name' => 'Canvas Market Tote', 'price' => 9000, 'featured' => true],
            ['category' => 'Tote Bags', 'name' => 'Adire Print Tote', 'price' => 11000],
            ['category' => 'Tote Bags', 'name' => 'Everyday Utility Tote', 'price' => 8500],
            ['category' => 'Tote Bags', 'name' => 'Woven Raffia-Trim Tote', 'price' => 13500, 'featured' => true],
            ['category' => 'Hoodies & Sweatshirts', 'name' => 'Harmattan Fleece Hoodie', 'price' => 28000, 'featured' => true, 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Black', 'Forest']],
            ['category' => 'Hoodies & Sweatshirts', 'name' => 'Oversized Studio Hoodie', 'price' => 32000, 'sizes' => ['M', 'L', 'XL'], 'colors' => ['Black']],
            ['category' => 'Hoodies & Sweatshirts', 'name' => 'Crest Logo Hoodie', 'price' => 30000, 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Sand', 'Black']],
            ['category' => 'Accessories', 'name' => 'Dora Signature Cap', 'price' => 7500],
            ['category' => 'Accessories', 'name' => 'Ankara Bucket Hat', 'price' => 8000, 'featured' => true],
        ];

        foreach ($products as $index => $data) {
            $slug = Str::slug($data['name']);
            $category = Category::where('name', $data['category'])->first();
            $hasVariants = ! empty($data['sizes']) || ! empty($data['colors']);
            $background = $palette[$index % count($palette)];

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category?->id,
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => "The {$data['name']} — designed and made by hand at the Dora Creations studio. Premium fabric, small-batch production, built to last.",
                    'price_kobo' => $data['price'] * 100,
                    'compare_at_price_kobo' => null,
                    'sku' => 'DC-'.strtoupper(Str::random(6)),
                    'stock_quantity' => $hasVariants ? 0 : random_int(15, 60),
                    'has_variants' => $hasVariants,
                    'is_published' => true,
                    'is_featured' => $data['featured'] ?? false,
                ]
            );

            $product->images()->delete();
            foreach (range(1, 2) as $n) {
                $product->images()->create([
                    'path' => PlaceholderImage::store('public', "products/{$slug}-{$n}.svg", $data['name'], $background),
                    'alt_text' => $data['name'],
                    'sort_order' => $n,
                ]);
            }

            $product->variants()->delete();
            if ($hasVariants) {
                $sizes = $data['sizes'] ?? [null];
                $colors = $data['colors'] ?? [null];

                foreach ($sizes as $size) {
                    foreach ($colors as $color) {
                        $product->variants()->create([
                            'size' => $size,
                            'color' => $color,
                            'sku' => 'DC-'.strtoupper(Str::random(8)),
                            'price_kobo' => null,
                            'stock_quantity' => random_int(5, 25),
                        ]);
                    }
                }
            }
        }
    }
}
