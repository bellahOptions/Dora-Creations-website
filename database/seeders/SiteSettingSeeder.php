<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SiteSetting::current();

        $settings->update([
            'maintenance_mode' => false,
            'site_name' => 'Dora Creations',
            'meta_title' => 'Dora Creations, Nigerian-made fashion',
            'meta_description' => 'Tees, tote bags and more, designed and produced by hand by Dora Creations.',
            'contact_email' => 'hello@doracreations.test',
            'contact_phone' => '+2348012345678',
            'social_instagram' => 'https://instagram.com/doracreations',
            'social_twitter' => 'https://twitter.com/doracreations',
            'social_facebook' => 'https://facebook.com/doracreations',
            'shipping_flat_rate_kobo' => 250000,
            'free_shipping_threshold_kobo' => 5000000,
        ]);
    }
}
