<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserTranslation;
use Illuminate\Support\Facades\Hash;

class StaticUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create the static user
        $user = User::create([
            'email' => 'static_access@techmarket.api',
            'password' => Hash::make('StaticToken2024!'),
            'role' => 'user',
        ]);

        // Add translations
        $translations = [
            [
                'locale' => 'uz',
                'name' => 'Statik Foydalanuvchi',
            ],
            [
                'locale' => 'ru',
                'name' => 'Статический Пользователь',
            ],
            [
                'locale' => 'en',
                'name' => 'Static User',
            ],
        ];

        foreach ($translations as $translation) {
            Translation::create([
                'user_id' => $user->id,
                'locale' => $translation['locale'],
                'name' => $translation['name'],
            ]);
        }
    }
}
