<?php

namespace App\Models;

use App\Services\CurrencyService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'cart_token',
        'currency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function itemCount(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function subtotalKobo(): int
    {
        return (int) $this->items->sum(fn (CartItem $item) => $item->unit_price_kobo * $item->quantity);
    }

    public function formattedSubtotal(): string
    {
        return Money::ngn($this->subtotalKobo());
    }

    public function displaySubtotal(): string
    {
        return app(CurrencyService::class)->format($this->subtotalKobo());
    }
}
