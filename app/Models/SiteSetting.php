<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'maintenance_mode',
        'site_name',
        'meta_title',
        'meta_description',
        'contact_email',
        'contact_phone',
        'social_instagram',
        'social_twitter',
        'social_facebook',
        'shipping_flat_rate_kobo',
        'free_shipping_threshold_kobo',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
