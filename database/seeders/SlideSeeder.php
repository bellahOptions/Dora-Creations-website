<?php

namespace Database\Seeders;

use App\Models\Slide;
use App\Support\PlaceholderImage;
use Illuminate\Database\Seeder;

class SlideSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            ['headline' => 'Wear the craft', 'subheadline' => 'Handmade tees and totes from the Dora Creations studio', 'cta_label' => 'Shop new arrivals', 'cta_url' => '/shop', 'background' => '#1B1913'],
            ['headline' => 'Small batch. Built to last.', 'subheadline' => 'Every piece cut, printed and finished by hand', 'cta_label' => 'Explore tees', 'cta_url' => '/collections/tees', 'background' => '#A94A17'],
            ['headline' => 'Carry the culture', 'subheadline' => 'Canvas totes made for everyday Lagos', 'cta_label' => 'Shop tote bags', 'cta_url' => '/collections/tote-bags', 'background' => '#175641'],
        ];

        foreach ($slides as $index => $slide) {
            Slide::updateOrCreate(
                ['headline' => $slide['headline']],
                [
                    'subheadline' => $slide['subheadline'],
                    'image_path' => PlaceholderImage::storePlain('public', 'slides/slide-'.($index + 1).'.svg', $slide['background'], '#F8F4EC'),
                    'cta_label' => $slide['cta_label'],
                    'cta_url' => $slide['cta_url'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
