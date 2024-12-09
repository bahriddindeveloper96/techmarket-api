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
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1234567890'
        ]);
        User::create([
            'email' => 'seller@example.com',
            'password' => Hash::make('password'),
            'role' => 'seller',
            'phone' => '+1234567890'
        ]);
        
    }
}
