<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Recommends products from three signals, weighted roughly in order of
 * intent: what a customer has bought, wishlisted, and viewed, matched
 * against what other customers who bought the same things also bought.
 * Falls back to storefront-wide trending/newest products when there isn't
 * enough personal history to work with (a new visitor, or a young catalog).
 */
class RecommendationService
{
    public function forUser(?User $user, int $limit = 8): Collection
    {
        $seedProductIds = $user ? $this->signalProductIds($user) : collect();

        $recommended = $seedProductIds->isNotEmpty()
            ? $this->coOccurring($seedProductIds, $limit)
            : collect();

        if ($recommended->count() < $limit) {
            $exclude = $seedProductIds->merge($recommended->pluck('id'));
            $recommended = $recommended->merge($this->trending($limit - $recommended->count(), $exclude));
        }

        return $recommended->take($limit)->values();
    }

    /**
     * Products this customer has purchased, wishlisted, or recently viewed.
     */
    protected function signalProductIds(User $user): Collection
    {
        $purchased = OrderItem::whereHas('order', fn ($query) => $query
            ->where('user_id', $user->id)
            ->whereNotNull('paid_at'))
            ->pluck('product_id');

        $wishlisted = $user->wishlistedProducts()->pluck('products.id');

        $viewed = ActivityLog::query()
            ->where('type', ActivityLog::TYPE_VISITOR)
            ->where('causer_type', User::class)
            ->where('causer_id', $user->id)
            ->where('subject_type', Product::class)
            ->where('description', 'like', 'Viewed %')
            ->latest('created_at')
            ->limit(50)
            ->pluck('subject_id');

        return $purchased->merge($wishlisted)->merge($viewed)->unique()->values();
    }

    /**
     * "Other customers who bought/interacted with these also bought" —
     * products that show up in the same paid orders as the seed products.
     */
    protected function coOccurring(Collection $seedProductIds, int $limit): Collection
    {
        $orderIds = OrderItem::whereIn('product_id', $seedProductIds)->pluck('order_id')->unique();

        if ($orderIds->isEmpty()) {
            return collect();
        }

        $candidateCounts = OrderItem::whereIn('order_id', $orderIds)
            ->whereNotIn('product_id', $seedProductIds)
            ->selectRaw('product_id, COUNT(*) as co_count')
            ->groupBy('product_id')
            ->orderByDesc('co_count')
            ->limit($limit * 3)
            ->pluck('co_count', 'product_id');

        if ($candidateCounts->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->published()
            ->whereIn('id', $candidateCounts->keys())
            ->with('images')
            ->get()
            ->sortByDesc(fn (Product $product) => $candidateCounts[$product->id])
            ->values();
    }

    /**
     * Site-wide fallback: most-viewed products over the last 30 days, then
     * featured, then just the newest — whichever has enough to fill $limit.
     */
    protected function trending(int $limit, Collection $exclude): Collection
    {
        $viewCounts = ActivityLog::query()
            ->where('type', ActivityLog::TYPE_VISITOR)
            ->where('subject_type', Product::class)
            ->where('description', 'like', 'Viewed %')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('subject_id, COUNT(*) as views')
            ->groupBy('subject_id')
            ->orderByDesc('views')
            ->limit($limit * 3)
            ->pluck('views', 'subject_id');

        $products = collect();

        if ($viewCounts->isNotEmpty()) {
            $products = Product::query()
                ->published()
                ->whereIn('id', $viewCounts->keys())
                ->whereNotIn('id', $exclude)
                ->with('images')
                ->get()
                ->sortByDesc(fn (Product $product) => $viewCounts[$product->id] ?? 0)
                ->values();
        }

        if ($products->count() < $limit) {
            $more = Product::query()
                ->published()
                ->whereNotIn('id', $exclude->merge($products->pluck('id')))
                ->with('images')
                ->orderByDesc('is_featured')
                ->latest()
                ->limit($limit - $products->count())
                ->get();

            $products = $products->merge($more);
        }

        return $products->values();
    }
}
