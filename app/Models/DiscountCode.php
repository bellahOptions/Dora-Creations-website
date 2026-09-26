<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DiscountCode extends Model
{
    use HasUuid, LogsAdminActivity;

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

    /**
     * Atomically claim one use of this code.
     *
     * The cap used to be checked only when the order was created and counted
     * only once payment landed, so a "single use" code could be applied to a
     * burst of orders that all paid while used_count was still 0. Claiming at
     * order time — with the cap re-checked inside the UPDATE — closes that
     * window. Returns false when the code is already exhausted.
     */
    public function reserveUsage(): bool
    {
        $claimed = static::query()
            ->whereKey($this->getKey())
            ->where(fn ($query) => $query
                ->whereNull('max_uses')
                ->orWhereColumn('used_count', '<', 'max_uses'))
            ->increment('used_count');

        return $claimed > 0;
    }

    /**
     * Hand a claimed use back when the order it was claimed for never gets
     * paid for.
     */
    public function releaseUsage(): void
    {
        static::query()
            ->whereKey($this->getKey())
            ->where('used_count', '>', 0)
            ->decrement('used_count');
    }

    public function activityLogName(): string
    {
        return $this->code;
    }

    protected function activityLoggableAttributes(): array
    {
        return [
            'code' => 'Code',
            'is_active' => 'Active',
        ];
    }
}
