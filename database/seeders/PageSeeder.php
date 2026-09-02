<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => 'About Dora Creations',
                'content' => '<p>Dora Creations began as a one-woman creative studio in Nigeria, cutting, printing and sewing every first piece by hand. What started as custom prints for friends grew into a fashion label built on the same principle: small-batch, well-made, designed to be worn for years.</p><p>Every tee and tote you see in the shop is still made and finished by Dora and her small studio team.</p>',
                'meta_description' => 'The story behind Dora Creations — Nigerian-made fashion, designed and produced by hand.',
            ],
            [
                'slug' => 'design-and-printing',
                'title' => 'Design & Printing Studio',
                'content' => '<p>Alongside the fashion line, Dora Creations runs a creative design and printing studio — branded merchandise, event materials, custom apparel prints and more for businesses and individuals across Nigeria.</p><p>Get in touch to discuss a custom design or print run.</p>',
                'meta_description' => "Dora Creations' creative design and printing services for businesses and individuals.",
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'content' => '<p>Questions about an order, a custom print job, or anything else? Reach us at hello@doracreations.test or on Instagram @doracreations.</p>',
                'meta_description' => 'Get in touch with Dora Creations.',
            ],
            [
                'slug' => 'shipping-and-returns',
                'title' => 'Shipping & Returns',
                'content' => '<p>We ship nationwide across Nigeria, with delivery times of 2–5 business days depending on location. Free delivery applies on qualifying orders.</p><p>Got a sizing issue or a damaged item? Reach out within 7 days of delivery and we\'ll sort it out — exchange, repair or refund.</p>',
                'meta_description' => 'Shipping times and our returns policy.',
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page + ['is_published' => true]);
        }
    }
}
