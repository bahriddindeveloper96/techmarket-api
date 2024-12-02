<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'cash',
                'icon' => 'cash.svg',
                'is_active' => true,
                'settings' => json_encode([
                    'requires_change' => true,
                ]),
                'translations' => [
                    'en' => [
                        'name' => 'Cash on Delivery',
                        'description' => 'Pay with cash when your order is delivered'
                    ],
                    'ru' => [
                        'name' => 'Наличные при доставке',
                        'description' => 'Оплата наличными при получении заказа'
                    ],
                    'uz' => [
                        'name' => 'Naqd pul orqali',
                        'description' => 'Buyurtma yetkazib berilganda naqd pul orqali to\'lov'
                    ]
                ]
            ],
            [
                'code' => 'card',
                'icon' => 'card.svg',
                'is_active' => true,
                'settings' => json_encode([
                    'supported_cards' => ['uzcard', 'humo', 'visa', 'mastercard'],
                    'min_amount' => 1000,
                    'max_amount' => 100000000
                ]),
                'translations' => [
                    'en' => [
                        'name' => 'Credit/Debit Card',
                        'description' => 'Pay securely with your card'
                    ],
                    'ru' => [
                        'name' => 'Банковская карта',
                        'description' => 'Безопасная оплата картой'
                    ],
                    'uz' => [
                        'name' => 'Bank kartasi',
                        'description' => 'Bank kartasi orqali xavfsiz to\'lov'
                    ]
                ]
            ],
            [
                'code' => 'click',
                'icon' => 'click.svg',
                'is_active' => true,
                'settings' => json_encode([
                    'merchant_id' => 'test_merchant',
                    'service_id' => 'test_service',
                    'min_amount' => 1000,
                    'max_amount' => 10000000
                ]),
                'translations' => [
                    'en' => [
                        'name' => 'CLICK',
                        'description' => 'Pay with CLICK'
                    ],
                    'ru' => [
                        'name' => 'CLICK',
                        'description' => 'Оплата через CLICK'
                    ],
                    'uz' => [
                        'name' => 'CLICK',
                        'description' => 'CLICK orqali to\'lov'
                    ]
                ]
            ],
            [
                'code' => 'payme',
                'icon' => 'payme.svg',
                'is_active' => true,
                'settings' => json_encode([
                    'merchant_id' => 'test_merchant',
                    'min_amount' => 1000,
                    'max_amount' => 10000000
                ]),
                'translations' => [
                    'en' => [
                        'name' => 'Payme',
                        'description' => 'Pay with Payme'
                    ],
                    'ru' => [
                        'name' => 'Payme',
                        'description' => 'Оплата через Payme'
                    ],
                    'uz' => [
                        'name' => 'Payme',
                        'description' => 'Payme orqali to\'lov'
                    ]
                ]
            ]
        ];

        foreach ($methods as $method) {
            $translations = $method['translations'];
            unset($method['translations']);
            
            $paymentMethod = PaymentMethod::create($method);

            foreach ($translations as $locale => $translation) {
                $paymentMethod->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }
        }
    }
}
