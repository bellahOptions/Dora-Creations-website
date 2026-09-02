<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // rate_to_ngn = how many NGN equal 1 unit of that currency.
        $currencies = [
            ['code' => 'NGN', 'symbol' => '₦', 'rate_to_ngn' => 1, 'is_base' => true],
            ['code' => 'USD', 'symbol' => '$', 'rate_to_ngn' => 1600],
            ['code' => 'GBP', 'symbol' => '£', 'rate_to_ngn' => 2050],
            ['code' => 'EUR', 'symbol' => '€', 'rate_to_ngn' => 1750],
            ['code' => 'GHS', 'symbol' => 'GH₵', 'rate_to_ngn' => 105],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency + ['is_active' => true]
            );
        }
    }
}
