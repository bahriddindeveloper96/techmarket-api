<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            // UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            AttributeSeeder::class,
            DeliveryMethodSeeder::class,
            PaymentMethodSeeder::class,
            ProductReviewSeeder::class,
            FavoriteSeeder::class,
            CompareListSeeder::class,
        ]);
    }
}
