<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // First, let's create the attribute groups and attributes for smartphones
        $smartphonesCategory = Category::where('slug', 'smartphones')->first();
        
        if (!$smartphonesCategory) {
            throw new \Exception('Smartphones category not found. Please run CategorySeeder first.');
        }

        // Create or get attribute groups
        $mainFeatures = AttributeGroup::firstOrCreate(['name' => 'Asosiy xususiyatlar']);
        $techFeatures = AttributeGroup::firstOrCreate(['name' => 'Texnik xususiyatlar']);

        // Create or get attributes
        $brandAttr = $this->createOrGetAttribute($mainFeatures, 'brand', [
            'uz' => 'Brend',
            'ru' => 'Бренд',
            'en' => 'Brand'
        ]);

        $modelAttr = $this->createOrGetAttribute($mainFeatures, 'model', [
            'uz' => 'Model',
            'ru' => 'Модель',
            'en' => 'Model'
        ]);

        $colorAttr = $this->createOrGetAttribute($mainFeatures, 'color', [
            'uz' => 'Rangi',
            'ru' => 'Цвет',
            'en' => 'Color'
        ]);

        $ramAttr = $this->createOrGetAttribute($techFeatures, 'ram', [
            'uz' => 'Operativ xotira',
            'ru' => 'Оперативная память',
            'en' => 'RAM'
        ]);

        $storageAttr = $this->createOrGetAttribute($techFeatures, 'storage', [
            'uz' => 'Doimiy xotira',
            'ru' => 'Постоянная память',
            'en' => 'Storage'
        ]);

        $screenSizeAttr = $this->createOrGetAttribute($techFeatures, 'screen_size', [
            'uz' => 'Ekran o\'lchami',
            'ru' => 'Размер экрана',
            'en' => 'Screen size'
        ]);

        // Products data
        $products = [
            [
                'category_id' => $smartphonesCategory->id,
                'user_id' => 1,
                'slug' => 'iphone-15-pro',
                'active' => true,
                'featured' => true,
                'attributes' => [
                    $brandAttr->id => 'Apple',
                    $modelAttr->id => 'iPhone 15 Pro',
                    $screenSizeAttr->id => '6.1 inches',
                    $ramAttr->id => '8GB',
                    $storageAttr->id => '128GB-512GB'
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
                            $ramAttr->id => '8GB',
                            $storageAttr->id => '128GB',
                            $colorAttr->id => 'Black'
                        ],
                        'price' => 999.99,
                        'stock' => 50,
                        'images' => ['products/iphone15pro-black-1.jpg', 'products/iphone15pro-black-2.jpg']
                    ],
                    [
                        'attribute_values' => [
                            $ramAttr->id => '8GB',
                            $storageAttr->id => '256GB',
                            $colorAttr->id => 'Silver'
                        ],
                        'price' => 1099.99,
                        'stock' => 30,
                        'images' => ['products/iphone15pro-silver-1.jpg', 'products/iphone15pro-silver-2.jpg']
                    ],
                    [
                        'attribute_values' => [
                            $ramAttr->id => '8GB',
                            $storageAttr->id => '512GB',
                            $colorAttr->id => 'Gold'
                        ],
                        'price' => 1299.99,
                        'stock' => 20,
                        'images' => ['products/iphone15pro-gold-1.jpg', 'products/iphone15pro-gold-2.jpg']
                    ]
                ]
            ],
            [
                'category_id' => $smartphonesCategory->id,
                'user_id' => 1,
                'slug' => 'samsung-galaxy-s24-ultra',
                'active' => true,
                'featured' => true,
                'attributes' => [
                    $brandAttr->id => 'Samsung',
                    $modelAttr->id => 'Galaxy S24 Ultra',
                    $screenSizeAttr->id => '6.8 inches',
                    $ramAttr->id => '12GB',
                    $storageAttr->id => '256GB-1TB'
                ],
                'translations' => [
                    'en' => [
                        'name' => 'Samsung Galaxy S24 Ultra',
                        'description' => 'The ultimate Galaxy experience with advanced AI capabilities.'
                    ],
                    'ru' => [
                        'name' => 'Samsung Galaxy S24 Ultra',
                        'description' => 'Максимальные возможности Galaxy с продвинутым ИИ.'
                    ],
                    'uz' => [
                        'name' => 'Samsung Galaxy S24 Ultra',
                        'description' => 'Ilg\'or AI imkoniyatlariga ega eng zo\'r Galaxy tajribasi.'
                    ]
                ],
                'variants' => [
                    [
                        'attribute_values' => [
                            $ramAttr->id => '12GB',
                            $storageAttr->id => '256GB',
                            $colorAttr->id => 'Titanium Black'
                        ],
                        'price' => 1199.99,
                        'stock' => 40,
                        'images' => ['products/s24ultra-black-1.jpg', 'products/s24ultra-black-2.jpg']
                    ],
                    [
                        'attribute_values' => [
                            $ramAttr->id => '12GB',
                            $storageAttr->id => '512GB',
                            $colorAttr->id => 'Titanium Gray'
                        ],
                        'price' => 1399.99,
                        'stock' => 25,
                        'images' => ['products/s24ultra-gray-1.jpg', 'products/s24ultra-gray-2.jpg']
                    ],
                    [
                        'attribute_values' => [
                            $ramAttr->id => '12GB',
                            $storageAttr->id => '1TB',
                            $colorAttr->id => 'Titanium Violet'
                        ],
                        'price' => 1599.99,
                        'stock' => 15,
                        'images' => ['products/s24ultra-violet-1.jpg', 'products/s24ultra-violet-2.jpg']
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            DB::transaction(function () use ($productData) {
                // Create product
                $product = Product::create([
                    'category_id' => $productData['category_id'],
                    'user_id' => $productData['user_id'],
                    'slug' => $productData['slug'],
                    'active' => $productData['active'],
                    'featured' => $productData['featured']
                ]);

                // Create translations
                foreach ($productData['translations'] as $locale => $translation) {
                    ProductTranslation::create([
                        'product_id' => $product->id,
                        'locale' => $locale,
                        'name' => $translation['name'],
                        'description' => $translation['description']
                    ]);
                }

                // Save product attributes
                foreach ($productData['attributes'] as $attributeId => $value) {
                    $product->attributes()->attach($attributeId, ['value' => $value]);
                }

                // Create variants
                foreach ($productData['variants'] as $variantData) {
                    // Convert attribute values to the correct format
                    $attributeValuesFormatted = [];
                    foreach ($variantData['attribute_values'] as $attributeId => $value) {
                        $attribute = Attribute::find($attributeId);
                        if ($attribute) {
                            $attributeValuesFormatted[$attribute->name] = $value;
                        }
                    }

                    $variant = new ProductVariant([
                        'price' => $variantData['price'],
                        'stock' => $variantData['stock'],
                        'images' => $variantData['images'],
                        'sku' => strtoupper($product->slug . '-' . Str::random(5)),
                        'attribute_values' => $attributeValuesFormatted
                    ]);
                    $variant->product()->associate($product);
                    $variant->save();

                    // Save variant attributes
                    foreach ($variantData['attribute_values'] as $attributeId => $value) {
                        $variant->attributes()->attach($attributeId, ['value' => $value]);
                    }
                }
            });
        }
    }

    private function createOrGetAttribute($group, $name, $translations)
    {
        $attribute = Attribute::firstOrCreate(
            ['name' => $name],
            ['attribute_group_id' => $group->id]
        );

        // Add translations if they don't exist
        foreach ($translations as $locale => $translation) {
            $attribute->translations()->firstOrCreate(
                ['locale' => $locale],
                ['name' => $translation]
            );
        }

        return $attribute;
    }
}
