<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            [
                'category_id' => 1, // Smartphones
                'user_id' => 1,
                'slug' => 'iphone-15-pro',
                'active' => true,
                'featured' => true,
              //  'images' => ['iphone15pro-1.jpg', 'iphone15pro-2.jpg'], // Added images
                'attributes' => [
                    'Brand' => 'Apple',
                    'Display Size' => '6.1 inches',
                    'Display Resolution' => '2556 x 1179 pixels',
                    'Processor' => 'A17 Pro chip',
                    'Main Camera' => '48MP + 12MP + 12MP',
                    'Front Camera' => '12MP TrueDepth',
                    'Battery Capacity' => '3274 mAh',
                    'Fast Charging' => true,
                    '5G Support' => true,
                    'NFC' => true,
                    'Wireless Charging' => true,
                    'Water Resistance' => 'IP68'
                ],
                'translations' => [
                    'en' => [
                        'name' => 'iPhone 15 Pro',
                        'description' => 'The most advanced iPhone ever with A17 Pro chip and titanium design.'
                    ],
                    'ru' => [
                        'name' => 'iPhone 15 Pro',
                        'description' => 'Самый продвинутый iPhone с чипом A17 Pro и титановым корпусом.'
                    ],
                    'uz' => [
                        'name' => 'iPhone 15 Pro',
                        'description' => 'A17 Pro protsessori va titan dizaynli eng ilg\'or iPhone.'
                    ]
                ],
                'variants' => [
                    [
                        'attribute_values' => [
                            'RAM' => '8GB',
                            'Storage' => '128GB',
                            'Color' => 'Black'
                        ],
                        'price' => 999.99,
                        'stock' => 50,
                        'images' => ['http://127.0.0.1:8000/storage/products/1.webp', 'http://127.0.0.1:8000/storage/products/2.jpg'] // Variant images
                    ],
                    [
                        'attribute_values' => [
                            'RAM' => '8GB',
                            'Storage' => '256GB',
                            'Color' => 'Silver'
                        ],
                        'price' => 1099.99,
                        'stock' => 30,
                        'images' => ['http://127.0.0.1:8000/storage/products/3.jpg', 'http://127.0.0.1:8000/storage/products/1.jpg']
                    ],
                    [
                        'attribute_values' => [
                            'RAM' => '8GB',
                            'Storage' => '512GB',
                            'Color' => 'Gold'
                        ],
                        'price' => 1299.99,
                        'stock' => 20,
                        'images' => ['http://127.0.0.1:8000/storage/products/3.jpg', 'http://127.0.0.1:8000/storage/products/1.jpg']
                    ]
                ]
            ],
            [
                'category_id' => 1, // Smartphones
                'user_id' => 1,
                'slug' => 'samsung-galaxy-s24-ultra',
                'active' => true,
                'featured' => true,
               // 'images' => ['s24ultra-1.jpg', 's24ultra-2.jpg'], // Added images
                'attributes' => [
                    'Brand' => 'Samsung',
                    'Display Size' => '6.8 inches',
                    'Display Resolution' => '3088 x 1440 pixels',
                    'Processor' => 'Snapdragon 8 Gen 3',
                    'Main Camera' => '200MP + 12MP + 50MP + 10MP',
                    'Front Camera' => '12MP',
                    'Battery Capacity' => '5000 mAh',
                    'Fast Charging' => true,
                    '5G Support' => true,
                    'NFC' => true,
                    'Wireless Charging' => true,
                    'Water Resistance' => 'IP68'
                ],
                'translations' => [
                    'en' => [
                        'name' => 'Samsung Galaxy S24 Ultra',
                        'description' => 'The ultimate Galaxy experience with AI innovations.'
                    ],
                    'ru' => [
                        'name' => 'Samsung Galaxy S24 Ultra',
                        'description' => 'Максимальные возможности Galaxy с инновациями ИИ.'
                    ],
                    'uz' => [
                        'name' => 'Samsung Galaxy S24 Ultra',
                        'description' => 'Sun\'iy intellekt innovatsiyalari bilan eng zo\'r Galaxy tajribasi.'
                    ]
                ],
                'variants' => [
                    [
                        'attribute_values' => [
                            'RAM' => '12GB',
                            'Storage' => '256GB',
                            'Color' => 'Black'
                        ],
                        'price' => 1299.99,
                        'stock' => 40,
                        'images' => ['http://127.0.0.1:8000/storage/products/2.jpg', 'http://127.0.0.1:8000/storage/products/3.jpg']
                    ],
                    [
                        'attribute_values' => [
                            'RAM' => '12GB',
                            'Storage' => '512GB',
                            'Color' => 'Silver'
                        ],
                        'price' => 1399.99,
                        'stock' => 25,
                        'images' => ['http://127.0.0.1:8000/storage/products/1.jpg', 'http://127.0.0.1:8000/storage/products/3.jpg']
                    ],
                    [
                        'attribute_values' => [
                            'RAM' => '12GB',
                            'Storage' => '1TB',
                            'Color' => 'Blue'
                        ],
                        'price' => 1599.99,
                        'stock' => 15,
                        'images' => ['http://127.0.0.1:8000/storage/products/2.jpg', 'http://127.0.0.1:8000/storage/products/1.jpg']
                    ]
                ]
            ],
            [
                'category_id' => 2, // Laptops
                'user_id' => 1,
                'slug' => 'macbook-pro-16aa',
                'active' => true,
                'featured' => true,
              //  'images' => ['macbook-pro-16-1.jpg', 'macbook-pro-16-2.jpg'], // Added images
                'attributes' => [
                    'Brand' => 'Apple',
                    'Display Size' => '16.2 inches',
                    'Display Resolution' => '3456 x 2234 pixels',
                    'Processor' => 'M3 Pro/Max',
                    'Battery Capacity' => '100Wh',
                    'Fast Charging' => true
                ],
                'translations' => [
                    'en' => [
                        'name' => 'MacBook Pro 16"',
                        'description' => 'Supercharged by M3 Pro or M3 Max.'
                    ],
                    'ru' => [
                        'name' => 'MacBook Pro 16"',
                        'description' => 'Ускорен процессором M3 Pro или M3 Max.'
                    ],
                    'uz' => [
                        'name' => 'MacBook Pro 16"',
                        'description' => 'M3 Pro yoki M3 Max protsessori bilan kuchaytirilgan.'
                    ]
                ],
                'variants' => [
                    [
                        'attribute_values' => [
                            'RAM' => '16GB',
                            'Storage' => '512GB',
                            'Color' => 'Silver'
                        ],
                        'price' => 2499.99,
                        'stock' => 20,
                        'images' => ['http://127.0.0.1:8000/storage/products/3.jpg', 'http://127.0.0.1:8000/storage/products/1.jpg']
                    ],
                    [
                        'attribute_values' => [
                            'RAM' => '32GB',
                            'Storage' => '1TB',
                            'Color' => 'Black'
                        ],
                        'price' => 2999.99,
                        'stock' => 15,
                        'images' => ['http://127.0.0.1:8000/storage/products/2.jpg', 'http://127.0.0.1:8000/storage/products/3.jpg']
                    ]
                ]
                    ],
                    [
                        'category_id' => 1, // Smartphones
                        'user_id' => 1,
                        'slug' => 'iphone-15-pro2',
                        'active' => true,
                        'featured' => true,
                      //  'images' => ['iphone15pro-1.jpg', 'iphone15pro-2.jpg'], // Added images
                        'attributes' => [
                            'Brand' => 'Apple',
                            'Display Size' => '6.1 inches',
                            'Display Resolution' => '2556 x 1179 pixels',
                            'Processor' => 'A17 Pro chip',
                            'Main Camera' => '48MP + 12MP + 12MP',
                            'Front Camera' => '12MP TrueDepth',
                            'Battery Capacity' => '3274 mAh',
                            'Fast Charging' => true,
                            '5G Support' => true,
                            'NFC' => true,
                            'Wireless Charging' => true,
                            'Water Resistance' => 'IP68'
                        ],
                        'translations' => [
                            'en' => [
                                'name' => 'iPhone 15 Pro',
                                'description' => 'The most advanced iPhone ever with A17 Pro chip and titanium design.'
                            ],
                            'ru' => [
                                'name' => 'iPhone 15 Pro',
                                'description' => 'Самый продвинутый iPhone с чипом A17 Pro и титановым корпусом.'
                            ],
                            'uz' => [
                                'name' => 'iPhone 15 Pro',
                                'description' => 'A17 Pro protsessori va titan dizaynli eng ilg\'or iPhone.'
                            ]
                        ],
                        'variants' => [
                            [
                                'attribute_values' => [
                                    'RAM' => '8GB',
                                    'Storage' => '128GB',
                                    'Color' => 'Black'
                                ],
                                'price' => 999.99,
                                'stock' => 50,
                                'images' => ['http://127.0.0.1:8000/storage/products/1.jpg', 'http://127.0.0.1:8000/storage/products/2.jpg'] // Variant images
                            ],
                            [
                                'attribute_values' => [
                                    'RAM' => '8GB',
                                    'Storage' => '256GB',
                                    'Color' => 'Silver'
                                ],
                                'price' => 1099.99,
                                'stock' => 30,
                                'images' => ['http://127.0.0.1:8000/storage/products/3.jpg', 'http://127.0.0.1:8000/storage/products/1.jpg']
                            ],
                            [
                                'attribute_values' => [
                                    'RAM' => '8GB',
                                    'Storage' => '512GB',
                                    'Color' => 'Gold'
                                ],
                                'price' => 1299.99,
                                'stock' => 20,
                                'images' => ['http://127.0.0.1:8000/storage/products/3.jpg', 'http://127.0.0.1:8000/storage/products/1.jpg']
                            ]
                        ]
                    ],
                    [
                        'category_id' => 1, // Smartphones
                        'user_id' => 1,
                        'slug' => 'samsung-galaxy-s24-ultra2',
                        'active' => true,
                        'featured' => true,
                       // 'images' => ['s24ultra-1.jpg', 's24ultra-2.jpg'], // Added images
                        'attributes' => [
                            'Brand' => 'Samsung',
                            'Display Size' => '6.8 inches',
                            'Display Resolution' => '3088 x 1440 pixels',
                            'Processor' => 'Snapdragon 8 Gen 3',
                            'Main Camera' => '200MP + 12MP + 50MP + 10MP',
                            'Front Camera' => '12MP',
                            'Battery Capacity' => '5000 mAh',
                            'Fast Charging' => true,
                            '5G Support' => true,
                            'NFC' => true,
                            'Wireless Charging' => true,
                            'Water Resistance' => 'IP68'
                        ],
                        'translations' => [
                            'en' => [
                                'name' => 'Samsung Galaxy S24 Ultra',
                                'description' => 'The ultimate Galaxy experience with AI innovations.'
                            ],
                            'ru' => [
                                'name' => 'Samsung Galaxy S24 Ultra',
                                'description' => 'Максимальные возможности Galaxy с инновациями ИИ.'
                            ],
                            'uz' => [
                                'name' => 'Samsung Galaxy S24 Ultra',
                                'description' => 'Sun\'iy intellekt innovatsiyalari bilan eng zo\'r Galaxy tajribasi.'
                            ]
                        ],
                        'variants' => [
                            [
                                'attribute_values' => [
                                    'RAM' => '12GB',
                                    'Storage' => '256GB',
                                    'Color' => 'Black'
                                ],
                                'price' => 1299.99,
                                'stock' => 40,
                                'images' => ['http://127.0.0.1:8000/storage/products/2.jpg', 'http://127.0.0.1:8000/storage/products/3.jpg']
                            ],
                            [
                                'attribute_values' => [
                                    'RAM' => '12GB',
                                    'Storage' => '512GB',
                                    'Color' => 'Silver'
                                ],
                                'price' => 1399.99,
                                'stock' => 25,
                                'images' => ['http://127.0.0.1:8000/storage/products/1.jpg', 'http://127.0.0.1:8000/storage/products/3.jpg']
                            ],
                            [
                                'attribute_values' => [
                                    'RAM' => '12GB',
                                    'Storage' => '1TB',
                                    'Color' => 'Blue'
                                ],
                                'price' => 1599.99,
                                'stock' => 15,
                                'images' => ['http://127.0.0.1:8000/storage/products/2.jpg', 'http://127.0.0.1:8000/storage/products/1.jpg']
                            ]
                        ]
                    ],
                    [
                        'category_id' => 2, // Laptops
                        'user_id' => 1,
                        'slug' => 'macbook-pro-16a',
                        'active' => true,
                        'featured' => true,
                      //  'images' => ['macbook-pro-16-1.jpg', 'macbook-pro-16-2.jpg'], // Added images
                        'attributes' => [
                            'Brand' => 'Apple',
                            'Display Size' => '16.2 inches',
                            'Display Resolution' => '3456 x 2234 pixels',
                            'Processor' => 'M3 Pro/Max',
                            'Battery Capacity' => '100Wh',
                            'Fast Charging' => true
                        ],
                        'translations' => [
                            'en' => [
                                'name' => 'MacBook Pro 16"',
                                'description' => 'Supercharged by M3 Pro or M3 Max.'
                            ],
                            'ru' => [
                                'name' => 'MacBook Pro 16"',
                                'description' => 'Ускорен процессором M3 Pro или M3 Max.'
                            ],
                            'uz' => [
                                'name' => 'MacBook Pro 16"',
                                'description' => 'M3 Pro yoki M3 Max protsessori bilan kuchaytirilgan.'
                            ]
                        ],
                        'variants' => [
                            [
                                'attribute_values' => [
                                    'RAM' => '16GB',
                                    'Storage' => '512GB',
                                    'Color' => 'Silver'
                                ],
                                'price' => 2499.99,
                                'stock' => 20,
                                'images' => ['http://127.0.0.1:8000/storage/products/3.jpg', 'http://127.0.0.1:8000/storage/products/1.jpg']
                            ],
                            [
                                'attribute_values' => [
                                    'RAM' => '32GB',
                                    'Storage' => '1TB',
                                    'Color' => 'Black'
                                ],
                                'price' => 2999.99,
                                'stock' => 15,
                                'images' => ['http://127.0.0.1:8000/storage/products/2.jpg', 'http://127.0.0.1:8000/storage/products/3.jpg']
                            ]
                        ]
                    ]
        ];

        foreach ($products as $productData) {
            $variants = $productData['variants'];
            $translations = $productData['translations'];
            $attributes = $productData['attributes'];

            unset($productData['variants'], $productData['translations'], $productData['attributes']);

            $product = Product::create($productData);

            // Create translations
            foreach ($translations as $locale => $translation) {
                ProductTranslation::create([
                    'product_id' => $product->id,
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }

            // Create variants
            foreach ($variants as $variantData) {
                $variant = new ProductVariant([
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'attribute_values' => $variantData['attribute_values'],
                    'images' => $variantData['images'] // Include images for the variant
                ]);
                $product->variants()->save($variant);
            }

            // Save attributes
            $product->attributes = $attributes;
            $product->save();
        }
    }
}