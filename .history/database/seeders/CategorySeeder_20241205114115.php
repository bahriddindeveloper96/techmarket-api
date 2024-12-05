<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'slug' => 'smartphones',
                'user_id' => 1,
                'active' => true,
                'featured' => true,
                'translations' => [
                    'en' => [
                        'name' => 'Smartphones',
                        'description' => 'Latest smartphones from top brands'
                    ],
                    'ru' => [
                        'name' => 'Смартфоны',
                        'description' => 'Новейшие смартфоны от ведущих брендов'
                    ],
                    'uz' => [
                        'name' => 'Smartfonlar',
                        'description' => 'Yetakchi brendlarning eng so\'nggi smartfonlari'
                    ]
                ]
            ],
            [
                'slug' => 'laptops',
                'user_id' => 1,
                'active' => true,
                'featured' => true,
                'translations' => [
                    'en' => [
                        'name' => 'Laptops',
                        'description' => 'Professional and gaming laptops'
                    ],
                    'ru' => [
                        'name' => 'Ноутбуки',
                        'description' => 'Профессиональные и игровые ноутбуки'
                    ],
                    'uz' => [
                        'name' => 'Noutbuklar',
                        'description' => 'Professional va o\'yin uchun noutbuklar'
                    ]
                ]
            ],
            [
                'slug' => 'tablets',
                'user_id' => 1,
                'active' => true,
                'featured' => false,
                'translations' => [
                    'en' => [
                        'name' => 'Tablets',
                        'description' => 'Tablets for work and entertainment'
                    ],
                    'ru' => [
                        'name' => 'Планшеты',
                        'description' => 'Планшеты для работы и развлечений'
                    ],
                    'uz' => [
                        'name' => 'Planshetlar',
                        'description' => 'Ish va ko\'ngil ochar vaqt uchun planshetlar'
                    ]
                ]
            ],
            [
                'slug' => 'accessories',
                'user_id' => 1,
                'active' => true,
                'featured' => false,
                'translations' => [
                    'en' => [
                        'name' => 'Accessories',
                        'description' => 'Device accessories and peripherals'
                    ],
                    'ru' => [
                        'name' => 'Аксессуары',
                        'description' => 'Аксессуары и периферия для устройств'
                    ],
                    'uz' => [
                        'name' => 'Aksessuarlar',
                        'description' => 'Qurilmalar uchun aksessuarlar va periferiya'
                    ]
                ]
            ],
            [
                'slug' => 'smart-watches',
                'user_id' => 1,
                'active' => true,
                'featured' => true,
                'translations' => [
                    'en' => [
                        'name' => 'Smart Watches',
                        'description' => 'Smart watches and fitness trackers'
                    ],
                    'ru' => [
                        'name' => 'Умные часы',
                        'description' => 'Умные часы и фитнес-трекеры'
                    ],
                    'uz' => [
                        'name' => 'Aqlli soatlar',
                        'description' => 'Aqlli soatlar va fitnes trekerlari'
                    ]
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            $translations = $categoryData['translations'];
            unset($categoryData['translations']);

            $category = Category::create($categoryData);

            foreach ($translations as $locale => $translation) {
                CategoryTranslation::create([
                    'category_id' => $category->id,
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }
        }
    }
}
