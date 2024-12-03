<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'phone' => '+998901234567',
                'role' => 'admin'
            ],
            [
                'email' => 'user1@example.com',
                'password' => bcrypt('password'),
                'phone' => '+998901234568',
                'role' => 'user'
            ],
            [
                'email' => 'user2@example.com',
                'password' => bcrypt('password'),
                'phone' => '+998901234569',
                'role' => 'user'
            ],
            [
                'email' => 'user3@example.com',
                'password' => bcrypt('password'),
                'phone' => '+998901234570',
                'role' => 'user'
            ]
        ];

        foreach ($users as $index => $userData) {
            $name = $index === 0 ? 'Admin User' : 'Test User ' . $index;
            $user = \App\Models\User::create($userData);

            // Add user translations
            $user->translations()->createMany([
                [
                    'locale' => 'uz',
                    'name' => $this->getUzbekName($name)
                ],
                [
                    'locale' => 'ru',
                    'name' => $this->getRussianName($name)
                ],
                [
                    'locale' => 'en',
                    'name' => $name
                ]
            ]);
        }
    }

    private function getUzbekName($name)
    {
        $names = [
            'Admin User' => 'Admin Foydalanuvchi',
            'Test User 1' => 'Test Foydalanuvchi 1',
            'Test User 2' => 'Test Foydalanuvchi 2',
            'Test User 3' => 'Test Foydalanuvchi 3'
        ];

        return $names[$name] ?? $name;
    }

    private function getRussianName($name)
    {
        $names = [
            'Admin User' => 'Админ Пользователь',
            'Test User 1' => 'Тестовый Пользователь 1',
            'Test User 2' => 'Тестовый Пользователь 2',
            'Test User 3' => 'Тестовый Пользователь 3'
        ];

        return $names[$name] ?? $name;
    }
}
