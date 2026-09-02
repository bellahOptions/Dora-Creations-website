<?php

namespace App\Models;

use App\Services\CurrencyService;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price_kobo',
        'compare_at_price_kobo',
        'sku',
        'stock_quantity',
        'has_variants',
        'is_published',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'has_variants' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function priceNaira(): float
    {
        return Money::naira($this->price_kobo);
    }

    public function formattedPrice(): string
    {
        return Money::ngn($this->price_kobo);
    }

    public function formattedCompareAtPrice(): ?string
    {
        return $this->compare_at_price_kobo ? Money::ngn($this->compare_at_price_kobo) : null;
    }

    /**
     * Price formatted in the visitor's selected display currency
     * (browsing only — checkout and receipts always show NGN, the
     * currency actually charged).
     */
    public function displayPrice(): string
    {
        return app(CurrencyService::class)->format($this->price_kobo);
    }

    public function displayCompareAtPrice(): ?string
    {
        if (! $this->compare_at_price_kobo) {
            return null;
        }

        return app(CurrencyService::class)->format($this->compare_at_price_kobo);
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price_kobo !== null && $this->compare_at_price_kobo > $this->price_kobo;
    }

    public function isInStock(): bool
    {
        if ($this->has_variants) {
            return $this->variants()->where('stock_quantity', '>', 0)->exists();
        }

        return $this->stock_quantity > 0;
    }

    public function averageRating(): float
    {
        return round($this->approvedReviews()->avg('rating') ?? 0, 1);
    }
}
