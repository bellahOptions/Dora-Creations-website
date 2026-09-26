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
        'bank_transfer_enabled',
        'bank_name',
        'bank_code',
        'bank_account_number',
        'bank_account_name',
        'bank_transfer_note',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
            'bank_transfer_enabled' => 'boolean',
        ];
    }

    /**
     * Bank transfer is only offered at checkout when the admin has switched
     * it on *and* there's a complete account for the customer to pay into.
     */
    public function bankTransferIsAvailable(): bool
    {
        return (bool) $this->bank_transfer_enabled
            && filled($this->bank_name)
            && filled($this->bank_account_number)
            && filled($this->bank_account_name);
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
