<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ivoirequiz.com'],
            [
                'name' => 'Yann-Morel Effobi ',
                'password' => Hash::make('Admin@2025!'),
                'friend_code' => 'ADMIN1',
                'avatar_id' => 1,
                'total_score' => 50000,
                'games_played' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
