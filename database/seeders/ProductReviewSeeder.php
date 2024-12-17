<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $products = \App\Models\Product::all();

        foreach ($products as $product) {
            // Create 3-7 reviews for each product
            $reviewCount = rand(3, 7);
            
            for ($i = 0; $i < $reviewCount; $i++) {
                \App\Models\ProductReview::create([
                    'user_id' => $users->random()->id,
                    'product_id' => $product->id,
                    'rating' => rand(3, 5),
                    'is_approved' => true,
                    'comment' => $this->getUzbekComment()
                ]);
            }
        }
    }

    private function getUzbekComment()
    {
        $comments = [
            'Ajoyib mahsulot, juda ham sifatli.',
            'Narxi sifatiga mos keladi.',
            'Yaxshi mahsulot, tavsiya qilaman.',
            'Sifati a\'lo darajada.',
            'Juda qulay va chiroyli.',
            'Kutganimdan ham yaxshiroq chiqdi.',
            'Zo\'r mahsulot, hammaga tavsiya qilaman.',
            'Sifati va narxi juda yaxshi.',
            'Juda yoqdi, rahmat.',
            'Yaxshi tanlov, pushaymon bo\'lmaysiz.'
        ];

        return $comments[array_rand($comments)];
    }
}
