<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaticUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Create the static user
        $user = User::create([
            'firstname' => 'Static',
            'lastname' => 'User',
            'email' => 'static@example.com',
            'password' => Hash::make('password'),
            'phone' => '+998901234567',
            'role' => 'user',
            'status' => 'active'
        ]);
    }
}
