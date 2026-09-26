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
        // robots.txt stays reachable during maintenance so it can tell
        // crawlers to back off (see RobotsController) instead of handing them
        // a 503 while still advertising a sitemap.
        if ($request->is('admin*') || $request->is('webhooks*') || $request->is('robots.txt')) {
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
