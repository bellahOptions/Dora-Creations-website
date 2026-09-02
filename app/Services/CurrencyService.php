<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

class CurrencyService
{
    public function active(): Collection
    {
        return Currency::active()->orderByDesc('is_base')->orderBy('code')->get();
    }

    /**
     * The visitor's selected display currency (session-backed), falling
     * back to the store's base currency (NGN) if unset or invalid.
     */
    public function current(): Currency
    {
        $code = session('currency');

        if ($code) {
            $currency = Currency::active()->where('code', $code)->first();

            if ($currency) {
                return $currency;
            }
        }

        return Currency::where('is_base', true)->first()
            ?? Currency::active()->first()
            ?? $this->fallbackBaseCurrency();
    }

    /**
     * Used only when the currencies table hasn't been seeded yet (e.g. a
     * fresh test database) — the store's real base currency is always NGN.
     */
    protected function fallbackBaseCurrency(): Currency
    {
        return new Currency([
            'code' => 'NGN',
            'symbol' => '₦',
            'rate_to_ngn' => 1,
            'is_base' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Format an NGN-kobo amount in the visitor's selected display currency.
     * Purely presentational — the store always charges in NGN.
     */
    public function format(int $ngnKobo): string
    {
        return $this->current()->format($ngnKobo);
    }
}
