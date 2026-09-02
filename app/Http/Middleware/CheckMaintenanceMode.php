<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Show a maintenance page to storefront visitors while admin routes,
     * webhooks, and already-authenticated admins stay reachable.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin*') || $request->is('webhooks*')) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->is_admin) {
            return $next($request);
        }

        if (SiteSetting::current()->maintenance_mode) {
            return response()->view('storefront.maintenance', status: 503);
        }

        return $next($request);
    }
}
