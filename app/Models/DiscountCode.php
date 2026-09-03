<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DiscountCode extends Model
{
    use HasUuid;

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'used_count',
        'min_order_kobo',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DiscountCode $discountCode) {
            $discountCode->code = Str::upper(trim($discountCode->code));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Look up a code and confirm it's usable against the given subtotal —
     * active, within its date window, under its usage cap, and meeting any
     * minimum order requirement. Returns null on any failure rather than
     * throwing, so callers can show one generic "invalid code" message.
     */
    public static function findValid(string $code, int $subtotalKobo): ?self
    {
        $discountCode = static::active()->where('code', Str::upper(trim($code)))->first();

        return $discountCode?->isValidFor($subtotalKobo) ? $discountCode : null;
    }

    public function isValidFor(int $subtotalKobo): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->min_order_kobo !== null && $subtotalKobo < $this->min_order_kobo) {
            return false;
        }

        return true;
    }

    /**
     * The discount amount in kobo for a given subtotal — never more than
     * the subtotal itself, so a total can't go negative.
     */
    public function calculateDiscount(int $subtotalKobo): int
    {
        $discount = $this->type === self::TYPE_PERCENTAGE
            ? (int) round($subtotalKobo * $this->value / 100)
            : $this->value;

        return min($discount, $subtotalKobo);
    }

    public function label(): string
    {
        return $this->type === self::TYPE_PERCENTAGE
            ? "{$this->value}% off"
            : \App\Support\Money::ngn($this->value).' off';
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}
