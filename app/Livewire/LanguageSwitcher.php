<?php

namespace App\Livewire;

use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale;

    public function mount(): void
    {
        $this->currentLocale = app()->getLocale();
    }

    public function selectLocale(string $locale): void
    {
        if (! array_key_exists($locale, config('locales'))) {
            return;
        }

        session(['locale' => $locale]);
        $this->currentLocale = $locale;

        // Translated strings are rendered in plain Blade across the storefront,
        // so a full reload is the simplest way to make every string on the
        // page reflect the new language consistently.
        $this->dispatch('locale-changed');
    }

    public function render()
    {
        return view('livewire.language-switcher', [
            'locales' => config('locales'),
        ]);
    }
}
