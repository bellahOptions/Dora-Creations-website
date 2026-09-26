<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\InvalidatesStorefrontCache;
use App\Models\Concerns\LogsAdminActivity;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AdModal extends Model
{
    use HasUuid, InvalidatesStorefrontCache, LogsAdminActivity;

    public const FREQUENCY_SESSION = 'session';

    public const FREQUENCY_EVERY_VISIT = 'every_visit';

    protected $fillable = [
        'title',
        'body',
        'image_path',
        'cta_label',
        'cta_url',
        'frequency',
        'delay_seconds',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Rendered unescaped in the modal shown on every storefront page, so it
     * is cleaned on write.
     */
    protected function body(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => HtmlSanitizer::clean($value));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return str_starts_with($this->image_path, 'http')
            ? $this->image_path
            : Storage::disk(config('filesystems.image_disk'))->url($this->image_path);
    }

    public function activityLogName(): string
    {
        return $this->title;
    }

    protected function activityLoggableAttributes(): array
    {
        return [
            'title' => 'Title',
            'is_active' => 'Active',
        ];
    }
}
