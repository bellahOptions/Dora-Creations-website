<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Services\CurrencyService;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'featured_image_path',
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

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->sku ??= static::generateSku();
        });
    }

    /**
     * DC-XXXXXX, retried until it doesn't collide — every product gets one
     * automatically, whether created via the admin panel, a seeder, or code.
     */
    public static function generateSku(): string
    {
        do {
            $sku = 'DC-'.strtoupper(Str::random(6));
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function featuredImageUrl(): ?string
    {
        if ($this->featured_image_path) {
            return str_starts_with($this->featured_image_path, 'http')
                ? $this->featured_image_path
                : Storage::disk('public')->url($this->featured_image_path);
        }

        return $this->images->first()?->url();
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
