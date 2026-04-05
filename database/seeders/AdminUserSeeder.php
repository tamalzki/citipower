<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'citipower@admin.com'],
            [
                'name' => 'CitiPower Admin',
                'role' => 'owner',
                'password' => Hash::make('citipoweradmin2026'),
                'email_verified_at' => now(),
            ]
        );
    }
}

