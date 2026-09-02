<?php

namespace App\Livewire;

use Livewire\Component;

class CurrencySwitcher extends Component
{
    public string $currency;

    /**
     * Placeholder list until the Currency model lands; codes map to display symbols.
     */
    public array $currencies = [
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
        'EUR' => '€',
        'GHS' => '₵',
    ];

    public function mount(): void
    {
        $this->currency = session('currency', 'NGN');
    }

    public function selectCurrency(string $code): void
    {
        if (! array_key_exists($code, $this->currencies)) {
            return;
        }

        $this->currency = $code;
        session(['currency' => $code]);

        $this->dispatch('currency-changed', currency: $code);
    }

    public function render()
    {
        return view('livewire.currency-switcher');
    }
}
