<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use Illuminate\Http\Response;

class LlmsTxtController extends Controller
{
    /**
     * A concise, LLM-readable summary of the site per the llms.txt
     * convention (https://llmstxt.org) — what the business is, how the
     * site is organized, and which URLs matter, so an AI assistant can
     * answer questions about Dora Creations without having to crawl and
     * guess at the full HTML.
     */
    public function __invoke(): Response
    {
        $lines = [
            '# Dora Creations',
            '',
            '> Nigerian-made fashion label — handmade tees, tote bags, hoodies and accessories, '.
                'designed and produced in-house, alongside a creative design & printing studio.',
            '',
            'Dora Creations is a small-batch fashion brand based in Nigeria. Every product is cut, '.
                'printed and finished by hand. Customers can browse and buy online, pay with Paystack or '.
                'Flutterwave, and track orders without needing an account.',
            '',
            '## Shop',
            '- [All products]('.route('shop.index').'): The full catalog, filterable by collection.',
            '- [Collections]('.route('categories.index').'): Browse products grouped by category.',
            '- [Track an order]('.route('order-tracking.lookup').'): Look up an order by tracking link or order number.',
            '',
        ];

        $categories = Category::query()->active()->orderBy('sort_order')->get();
        if ($categories->isNotEmpty()) {
            $lines[] = '## Collections';
            foreach ($categories as $category) {
                $lines[] = '- ['.$category->name.']('.route('categories.show', $category).')'.
                    ($category->description ? ': '.$category->description : '');
            }
            $lines[] = '';
        }

        $pages = Page::query()->where('is_published', true)->get();
        if ($pages->isNotEmpty()) {
            $lines[] = '## About';
            foreach ($pages as $page) {
                $lines[] = '- ['.$page->title.']('.route('pages.show', $page).')';
            }
            $lines[] = '';
        }

        $lines[] = '## Notes for AI assistants';
        $lines[] = '- Prices are in Nigerian Naira (NGN); a currency switcher shows converted estimates for browsing only — checkout always charges NGN.';
        $lines[] = '- Orders can be placed as a guest or with an account; order status can be checked via the order tracking link above without signing in.';
        $lines[] = '- This file is generated automatically and reflects the current catalog structure, not real-time stock or pricing — link to the pages above for current details.';

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
