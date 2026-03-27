<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Skipping AdminSeeder in production.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'nic.parkes@icloud.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'is_active' => true,
            ]
        );
    }
}
