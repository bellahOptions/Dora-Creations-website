<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('shop.index'), 'priority' => '0.9'],
            ['loc' => route('categories.index'), 'priority' => '0.8'],
            ['loc' => route('order-tracking.lookup'), 'priority' => '0.3'],
        ]);

        Category::query()->active()->get()->each(function (Category $category) use ($urls) {
            $urls->push([
                'loc' => route('categories.show', $category),
                'priority' => '0.7',
                'lastmod' => $category->updated_at?->toAtomString(),
            ]);
        });

        Product::query()->published()->get()->each(function (Product $product) use ($urls) {
            $urls->push([
                'loc' => route('shop.show', $product),
                'priority' => '0.8',
                'lastmod' => $product->updated_at?->toAtomString(),
            ]);
        });

        Page::query()->where('is_published', true)->get()->each(function (Page $page) use ($urls) {
            $urls->push([
                'loc' => route('pages.show', $page),
                'priority' => '0.5',
                'lastmod' => $page->updated_at?->toAtomString(),
            ]);
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
