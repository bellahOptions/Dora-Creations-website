<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CurrencySeeder::class,
            SiteSettingSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            SlideSeeder::class,
            PageSeeder::class,
        ]);
    }
}
