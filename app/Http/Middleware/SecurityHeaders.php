<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Baseline security headers for every response. The CSP stays
     * permissive on script/style ('unsafe-inline'/'unsafe-eval') because
     * Livewire and Alpine.js rely on both throughout the app — the value
     * here is blocking framing, plugins, and unexpected connect/base
     * targets, not locking down script execution.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Drop PHP's own version-fingerprinting header — no need to tell
        // the world exactly which PHP build is running behind the app.
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // The Vite dev server (npm/composer run dev) serves assets and HMR
        // websocket traffic from its own origin/port, which a strict 'self'
        // CSP blocks outright. Only relax script/style/connect-src for that
        // origin in local development — production always builds assets
        // ahead of time and serves them same-origin, so this never applies.
        // Chrome's CSP parser rejects the bracketed IPv6 loopback form
        // ([::1]) outright, in any syntax — vite.config.js pins the dev
        // server to the IPv4 loopback instead, so only that needs allowing.
        $viteDevOrigins = app()->environment('local')
            ? 'http://localhost:* http://127.0.0.1:*'
            : '';
        $viteDevSockets = app()->environment('local')
            ? 'ws://localhost:* ws://127.0.0.1:*'
            : '';

        $response->headers->set('Content-Security-Policy', implode('; ', array_filter([
            "default-src 'self'",
            trim("script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteDevOrigins}"),
            trim("style-src 'self' 'unsafe-inline' https://fonts.bunny.net {$viteDevOrigins}"),
            trim("font-src 'self' https://fonts.bunny.net data: {$viteDevOrigins}"),
            "img-src 'self' data: https:",
            trim("connect-src 'self' {$viteDevOrigins} {$viteDevSockets}"),
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
        ])));

        return $response;
    }
}
