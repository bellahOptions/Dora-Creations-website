<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\LogsAdminActivity;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory, HasUuid, LogsAdminActivity;

    protected $fillable = [
        'code',
        'symbol',
        'rate_to_ngn',
        'is_base',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_to_ngn' => 'decimal:4',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ];
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
     * Convert an NGN kobo amount into this currency's minor-unit-free display amount.
     */
    public function convertFromNgnKobo(int $ngnKobo): float
    {
        $ngn = $ngnKobo / 100;

        return round($ngn / (float) $this->rate_to_ngn, 2);
    }

    public function format(int $ngnKobo): string
    {
        return $this->symbol.number_format($this->convertFromNgnKobo($ngnKobo), 2);
    }

    public function activityLogName(): string
    {
        return $this->code;
    }

    protected function activityLoggableAttributes(): array
    {
        return [
            'rate_to_ngn' => 'Exchange rate',
            'is_active' => 'Active',
        ];
    }
}
