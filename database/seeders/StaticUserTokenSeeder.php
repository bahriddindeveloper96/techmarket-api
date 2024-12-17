<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaticUserTokenSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'firstname' => 'Static',
            'lastname' => 'Token User',
            'bio' => 'User for static token authentication',
            'address' => 'System',
            'email' => 'static@example.com',
            'password' => Hash::make('static_token_user'),
            'phone' => '+998900000000',
            'role' => 'user',
        ]);

        // Create a static token for this user
        $token = $user->createToken('static-token')->plainTextToken;
        
        // You can output the token or store it somewhere secure
        echo "Static Token: " . $token . "\n";
    }
}
