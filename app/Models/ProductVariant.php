<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size',
        'color',
        'sku',
        'price_kobo',
        'stock_quantity',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceKobo(): int
    {
        return $this->price_kobo ?? $this->product->price_kobo;
    }

    public function formattedPrice(): string
    {
        return Money::ngn($this->priceKobo());
    }

    public function label(): string
    {
        return collect([$this->size, $this->color])->filter()->implode(' / ');
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }
}
