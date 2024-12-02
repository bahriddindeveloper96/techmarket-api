<?php

namespace Database\Seeders;

use App\Models\DeliveryMethod;
use Illuminate\Database\Seeder;

class DeliveryMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'standard',
                'base_cost' => 30000,
                'estimated_days' => 3,
                'is_active' => true,
                'settings' => json_encode([
                    'max_weight' => 20,
                    'max_dimensions' => [
                        'length' => 100,
                        'width' => 100,
                        'height' => 100
                    ]
                ]),
                'translations' => [
                    'en' => [
                        'name' => 'Standard Delivery',
                        'description' => 'Standard delivery within 3 working days'
                    ],
                    'ru' => [
                        'name' => 'Стандартная доставка',
                        'description' => 'Стандартная доставка в течение 3 рабочих дней'
                    ],
                    'uz' => [
                        'name' => 'Standart yetkazib berish',
                        'description' => '3 ish kuni ichida standart yetkazib berish'
                    ]
                ]
            ],
            [
                'code' => 'express',
                'base_cost' => 50000,
                'estimated_days' => 1,
                'is_active' => true,
                'settings' => json_encode([
                    'max_weight' => 10,
                    'max_dimensions' => [
                        'length' => 50,
                        'width' => 50,
                        'height' => 50
                    ]
                ]),
                'translations' => [
                    'en' => [
                        'name' => 'Express Delivery',
                        'description' => 'Express delivery within 24 hours'
                    ],
                    'ru' => [
                        'name' => 'Экспресс доставка',
                        'description' => 'Экспресс доставка в течение 24 часов'
                    ],
                    'uz' => [
                        'name' => 'Tezkor yetkazib berish',
                        'description' => '24 soat ichida tezkor yetkazib berish'
                    ]
                ]
            ],
            [
                'code' => 'pickup',
                'base_cost' => 0,
                'estimated_days' => 0,
                'is_active' => true,
                'settings' => json_encode([
                    'pickup_points' => [
                        [
                            'address' => 'Main Store, 123 Street',
                            'working_hours' => '09:00-18:00'
                        ]
                    ]
                ]),
                'translations' => [
                    'en' => [
                        'name' => 'Pickup from Store',
                        'description' => 'Pick up your order from our store'
                    ],
                    'ru' => [
                        'name' => 'Самовывоз',
                        'description' => 'Заберите заказ из нашего магазина'
                    ],
                    'uz' => [
                        'name' => 'Do\'kondan olib ketish',
                        'description' => 'Buyurtmangizni do\'konimizdan olib keting'
                    ]
                ]
            ]
        ];

        foreach ($methods as $method) {
            $translations = $method['translations'];
            unset($method['translations']);
            
            $deliveryMethod = DeliveryMethod::create($method);

            foreach ($translations as $locale => $translation) {
                $deliveryMethod->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }
        }
    }
}
