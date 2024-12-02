<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // First create categories
        $this->call(CategorySeeder::class);

        // Then create attributes and attribute groups
        $this->call(AttributeSeeder::class);

        // Finally create products with variants
        $this->call(ProductSeeder::class);
    }
}
