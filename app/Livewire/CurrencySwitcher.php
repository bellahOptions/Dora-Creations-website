<?php

namespace App\Livewire;

use App\Services\CurrencyService;
use Livewire\Component;

class CurrencySwitcher extends Component
{
    public string $currentCode;

    public function mount(CurrencyService $currencyService): void
    {
        $this->currentCode = $currencyService->current()->code;
    }

    public function selectCurrency(string $code, CurrencyService $currencyService): void
    {
        if (! $currencyService->active()->pluck('code')->contains($code)) {
            return;
        }

        session(['currency' => $code]);
        $this->currentCode = $code;

        // Prices are rendered in plain Blade across the storefront, so a
        // full reload is the simplest way to make every price on the page
        // reflect the new currency consistently.
        $this->dispatch('currency-changed');
    }

    public function render(CurrencyService $currencyService)
    {
        return view('livewire.currency-switcher', [
            'currencies' => $currencyService->active(),
        ]);
    }
}
