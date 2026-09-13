<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the visitor's manually-selected storefront language (session-backed),
     * falling back to the app's default locale if unset or unsupported.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');

        if ($locale && array_key_exists($locale, config('locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
