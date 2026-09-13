<?php

namespace App\Models\Concerns;

use App\Services\StorefrontCache;

/**
 * Bumps the shared storefront cache version whenever a model that feeds the
 * shop listing (Product, Category) is saved or deleted, so cached product
 * pages and filter results never go stale — regardless of whether the change
 * came from the Filament admin panel, a seeder, or a queued job.
 */
trait InvalidatesStorefrontCache
{
    public static function bootInvalidatesStorefrontCache(): void
    {
        static::saved(fn () => StorefrontCache::flush());
        static::deleted(fn () => StorefrontCache::flush());
    }
}
