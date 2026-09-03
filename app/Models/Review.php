<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'reviewer_name',
        'order_item_id',
        'rating',
        'title',
        'body',
        'screenshot_path',
        'is_verified_purchase',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'is_verified_purchase' => 'boolean',
            'rating' => 'integer',
        ];
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->reviewer_name ?? 'Verified buyer';
    }

    public function screenshotUrl(): ?string
    {
        if (! $this->screenshot_path) {
            return null;
        }

        return str_starts_with($this->screenshot_path, 'http')
            ? $this->screenshot_path
            : Storage::disk(config('filesystems.image_disk'))->url($this->screenshot_path);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }
}
