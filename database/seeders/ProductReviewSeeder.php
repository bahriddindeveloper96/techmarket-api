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
                $review = \App\Models\ProductReview::create([
                    'user_id' => $users->random()->id,
                    'product_id' => $product->id,
                    'rating' => rand(3, 5),
                    'is_approved' => true
                ]);

                // Add review translations
                $review->translations()->createMany([
                    [
                        'locale' => 'uz',
                        'comment' => $this->getUzbekComment()
                    ],
                    [
                        'locale' => 'ru',
                        'comment' => $this->getRussianComment()
                    ],
                    [
                        'locale' => 'en',
                        'comment' => $this->getEnglishComment()
                    ]
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

    private function getRussianComment()
    {
        $comments = [
            'Отличный товар, очень качественный.',
            'Цена соответствует качеству.',
            'Хороший товар, рекомендую.',
            'Качество на высшем уровне.',
            'Очень удобный и красивый.',
            'Превзошел мои ожидания.',
            'Отличный товар, всем рекомендую.',
            'Качество и цена очень хорошие.',
            'Очень понравился, спасибо.',
            'Хороший выбор, не пожалеете.'
        ];

        return $comments[array_rand($comments)];
    }

    private function getEnglishComment()
    {
        $comments = [
            'Excellent product, very high quality.',
            'Price matches the quality.',
            'Good product, I recommend it.',
            'Quality is top-notch.',
            'Very convenient and beautiful.',
            'Exceeded my expectations.',
            'Great product, highly recommend.',
            'Quality and price are very good.',
            'Really liked it, thank you.',
            'Good choice, you won\'t regret it.'
        ];

        return $comments[array_rand($comments)];
    }
}
