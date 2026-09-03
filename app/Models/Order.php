<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUuid;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DELIVERY_ONGOING = 'delivery_ongoing';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_REJECTED_REFUNDED = 'rejected_refunded';

    public const STATUS_REVIEW_REQUESTED = 'review_requested';

    /**
     * Statuses an admin can move an order through after payment is confirmed.
     */
    public const MANAGEABLE_STATUSES = [
        self::STATUS_PROCESSING,
        self::STATUS_DELIVERY_ONGOING,
        self::STATUS_DELIVERED,
        self::STATUS_REJECTED_REFUNDED,
        self::STATUS_REVIEW_REQUESTED,
    ];

    protected $fillable = [
        'order_number',
        'public_token',
        'user_id',
        'guest_email',
        'status',
        'display_currency',
        'subtotal_kobo',
        'shipping_kobo',
        'total_kobo',
        'shipping_full_name',
        'shipping_phone',
        'shipping_country',
        'shipping_state',
        'shipping_city',
        'shipping_line1',
        'shipping_line2',
        'shipping_postal_code',
        'payment_gateway',
        'payment_reference',
        'paid_at',
        'customer_note',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Admin panel URLs (/admin/orders/{record}) resolve by uuid, not the
     * sequential id, so they don't reveal order volume. Customer-facing
     * order tracking uses its own public_token and never touches this.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->order_number ??= static::generateOrderNumber();
            $order->public_token ??= (string) Str::uuid();
        });
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'DC-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recordStatus(string $status, ?string $note = null, ?User $changedBy = null): void
    {
        $this->update(['status' => $status]);

        $this->statusHistories()->create([
            'status' => $status,
            'note' => $note,
            'changed_by' => $changedBy?->id,
        ]);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function customerName(): string
    {
        return $this->user?->name ?? $this->shipping_full_name;
    }

    public function customerEmail(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    public function formattedTotal(): string
    {
        return Money::ngn($this->total_kobo);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_PAYMENT => 'Pending payment',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_DELIVERY_ONGOING => 'Delivery ongoing',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_REJECTED_REFUNDED => 'Rejected / Refunded',
            self::STATUS_REVIEW_REQUESTED => 'Review requested',
            default => Str::headline($this->status),
        };
    }
}
