<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@likehome.md'],
            [
                'name' => 'Administrator',
                'password' => 'ChangeMeNow!2026',
                'role' => 'admin',
                'status' => 'active',
            ],
        );
    }
}
