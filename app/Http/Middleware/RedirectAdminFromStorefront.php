<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminFromStorefront
{
    /**
     * Admin accounts are staff, not customers: keep them confined to the
     * /admin panel so they never browse, checkout, or hold a customer
     * profile on the storefront side of the app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            return $next($request);
        }

        if ($request->is('admin*', 'webhooks*', 'livewire*', 'storage*', 'logout', 'up')) {
            return $next($request);
        }

        return redirect()->route('filament.admin.pages.dashboard');
    }
}
