<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Kouassi Amon',
                'email' => 'kouassi@test.com',
                'password' => Hash::make('Test@2025!'),
                'friend_code' => 'KOU001',
                'avatar_id' => 2,
                'total_score' => 3250,
                'games_played' => 8,
            ],
            [
                'name' => 'Adjoua Bamba',
                'email' => 'adjoua@test.com',
                'password' => Hash::make('Test@2025!'),
                'friend_code' => 'ADJ002',
                'avatar_id' => 5,
                'total_score' => 2800,
                'games_played' => 6,
            ],
            [
                'name' => 'Koné Mamadou',
                'email' => 'kone@test.com',
                'password' => Hash::make('Test@2025!'),
                'friend_code' => 'KON003',
                'avatar_id' => 3,
                'total_score' => 4100,
                'games_played' => 12,
            ],
            [
                'name' => 'Yao Serge',
                'email' => 'yao@test.com',
                'password' => Hash::make('Test@2025!'),
                'friend_code' => 'YAO004',
                'avatar_id' => 7,
                'total_score' => 1500,
                'games_played' => 4,
            ],
            [
                'name' => 'Ahou Cécile',
                'email' => 'ahou@test.com',
                'password' => Hash::make('Test@2025!'),
                'friend_code' => 'AHO005',
                'avatar_id' => 4,
                'total_score' => 5200,
                'games_played' => 15,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $user['password'],
                    'friend_code' => $user['friend_code'],
                    'avatar_id' => $user['avatar_id'],
                    'total_score' => $user['total_score'],
                    'games_played' => $user['games_played'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
