<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\LogsAdminActivity;
use Database\Factories\SlideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Slide extends Model
{
    /** @use HasFactory<SlideFactory> */
    use HasFactory, HasUuid, LogsAdminActivity;

    protected $fillable = [
        'headline',
        'subheadline',
        'image_path',
        'cta_label',
        'cta_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function url(): string
    {
        return str_starts_with($this->image_path, 'http')
            ? $this->image_path
            : Storage::disk(config('filesystems.image_disk'))->url($this->image_path);
    }

    public function activityLogName(): string
    {
        return $this->headline;
    }

    protected function activityLoggableAttributes(): array
    {
        return [
            'headline' => 'Headline',
            'is_active' => 'Active',
        ];
    }
}
