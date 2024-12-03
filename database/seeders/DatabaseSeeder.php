<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
          
            AttributeSeeder::class,
            DeliveryMethodSeeder::class,
            PaymentMethodSeeder::class,
        ]);
    }
}
