<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'firstname' => 'Admin',
            'lastname' => 'Baxa',
            'bio' => 'System administrator',
            'address' => 'Tashkent, Uzbekistan',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '+998901234567',
            'role' => 'admin',
        ]);
    }
}
