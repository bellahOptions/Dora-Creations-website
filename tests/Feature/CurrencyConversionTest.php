<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::create(['code' => 'NGN', 'symbol' => '₦', 'rate_to_ngn' => 1, 'is_base' => true, 'is_active' => true]);
        Currency::create(['code' => 'USD', 'symbol' => '$', 'rate_to_ngn' => 1600, 'is_base' => false, 'is_active' => true]);
    }

    public function test_it_defaults_to_the_base_currency_when_none_selected(): void
    {
        $formatted = app(CurrencyService::class)->format(1000000);

        $this->assertSame('₦10,000.00', $formatted);
    }

    public function test_it_converts_using_the_selected_currencys_rate(): void
    {
        session(['currency' => 'USD']);

        $formatted = app(CurrencyService::class)->format(1600000);

        $this->assertSame('$10.00', $formatted);
    }

    public function test_it_falls_back_to_base_currency_for_an_unknown_or_inactive_code(): void
    {
        session(['currency' => 'ZZZ']);

        $formatted = app(CurrencyService::class)->format(1000000);

        $this->assertSame('₦10,000.00', $formatted);
    }

    public function test_inactive_currencies_are_excluded_from_the_active_list(): void
    {
        Currency::create(['code' => 'GBP', 'symbol' => '£', 'rate_to_ngn' => 2000, 'is_active' => false]);

        $codes = app(CurrencyService::class)->active()->pluck('code');

        $this->assertTrue($codes->contains('USD'));
        $this->assertFalse($codes->contains('GBP'));
    }
}
