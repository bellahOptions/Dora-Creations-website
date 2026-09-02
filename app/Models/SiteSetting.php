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
        // Explicit defaults, not just DB column defaults: firstOrCreate()'s
        // returned instance only reflects attributes it actually set, so a
        // bare ['id' => 1] leaves shipping_flat_rate_kobo etc. null in
        // memory even though the DB applied its own default on insert.
        return static::query()->firstOrCreate(['id' => 1], [
            'maintenance_mode' => false,
            'site_name' => 'Dora Creations',
            'shipping_flat_rate_kobo' => 250000,
        ]);
    }
}
