<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            StaticUserTokenSeeder::class,
            AttributeSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            BannerSeeder::class,
            FavoriteSeeder::class,
            DeliveryMethodSeeder::class,
            PaymentMethodSeeder::class,
            ProductReviewSeeder::class,
           // StaticUserSeeder::class
        ]);
    }
}
