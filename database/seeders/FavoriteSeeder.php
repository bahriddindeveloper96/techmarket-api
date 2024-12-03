<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $products = \App\Models\Product::all();

        foreach ($users as $user) {
            // Each user will have 1-3 favorite products
            $favoriteCount = min(rand(1, 3), $products->count());
            $randomProducts = $products->random($favoriteCount);

            foreach ($randomProducts as $product) {
                \App\Models\Favorite::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id
                ]);
            }
        }
    }
}
