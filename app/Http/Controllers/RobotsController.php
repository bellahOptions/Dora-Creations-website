<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        // While maintenance mode is on every storefront URL answers 503, so
        // robots.txt must stop pointing crawlers at the sitemap and tell them
        // to stay away until the shop is back.
        if (SiteSetting::current()->maintenance_mode) {
            return response("User-agent: *\nDisallow: /\n", 200)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        $disallow = [
            '/admin', '/cart', '/checkout', '/account',
            '/login', '/register', '/forgot-password', '/reset-password',
            '/confirm-password', '/verify-email',
            // Tokenised order pages render real content but are private, and
            // /livewire/update is a POST-only endpoint crawlers shouldn't hit.
            '/track-order', '/wishlist', '/livewire',
        ];

        $lines = ['User-agent: *'];
        foreach ($disallow as $path) {
            $lines[] = "Disallow: {$path}";
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
