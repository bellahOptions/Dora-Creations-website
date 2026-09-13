<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Version-based cache invalidation for storefront product/category listings.
 *
 * The app's cache driver is `database`, which doesn't support tagging, so
 * instead of tagging entries this keeps a single version number and folds it
 * into every cache key. Bumping the version (see InvalidatesStorefrontCache)
 * makes every previously cached key unreachable at once; the old rows just
 * sit until their TTL expires rather than being deleted explicitly.
 */
class StorefrontCache
{
    private const VERSION_KEY = 'storefront:cache:version';

    private const TTL_SECONDS = 600;

    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember(
            'storefront:v'.static::version().':'.$key,
            static::TTL_SECONDS,
            $callback,
        );
    }

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function flush(): void
    {
        Cache::put(self::VERSION_KEY, static::version() + 1, now()->addMonth());
    }
}
