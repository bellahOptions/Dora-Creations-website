<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\RedirectAdminFromStorefront;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Behind a load balancer/reverse proxy in production, set TRUSTED_PROXIES
        // (e.g. to the proxy's IP, or '*' if it's a managed platform you trust)
        // so Laravel reads X-Forwarded-* headers for the real client IP/scheme.
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $trustedProxies === '*' ? '*' : explode(',', $trustedProxies));
        }

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->web(append: [
            RedirectAdminFromStorefront::class,
            CheckMaintenanceMode::class,
        ]);

        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
