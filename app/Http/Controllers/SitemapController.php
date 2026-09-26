<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Services\StorefrontCache;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        // Crawlers hit this often and it scans three tables; the version-keyed
        // storefront cache means admin edits still invalidate it immediately.
        $xml = StorefrontCache::remember('sitemap.xml', function (): string {
            $urls = collect([
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('shop.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('categories.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
                ['loc' => route('order-tracking.lookup'), 'priority' => '0.3', 'changefreq' => 'monthly'],
            ]);

            Category::query()->active()->get()->each(function (Category $category) use ($urls) {
                $urls->push([
                    'loc' => route('categories.show', $category),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                    'lastmod' => $category->updated_at?->toAtomString(),
                ]);
            });

            Product::query()->published()->get()->each(function (Product $product) use ($urls) {
                $urls->push([
                    'loc' => route('shop.show', $product),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => $product->updated_at?->toAtomString(),
                ]);
            });

            Page::query()->where('is_published', true)->get()->each(function (Page $page) use ($urls) {
                $urls->push([
                    'loc' => route('pages.show', $page),
                    'priority' => '0.5',
                    'changefreq' => 'monthly',
                    'lastmod' => $page->updated_at?->toAtomString(),
                ]);
            });

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
