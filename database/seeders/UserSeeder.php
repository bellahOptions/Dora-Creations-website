<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@doracreations.test'],
            [
                'name' => 'Dora Admin',
                'phone' => '+2348012345678',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@doracreations.test'],
            [
                'name' => 'Ada Customer',
                'phone' => '+2348098765432',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );
    }
}
