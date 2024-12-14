<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run()
    {
        $banners = [
            [
                'title' => 'Special Offer',
                'description' => 'Get 20% off on all products',
                'image' => 'http://127.0.0.1:8000/storage/products/banner1.jpg',
                'url' => '/special-offers',
                'button_text' => 'Shop Now',
                'order' => 1,
                'active' => true
            ],
            [
                'title' => 'New Collection',
                'description' => 'Check out our new arrivals',
                'image' => '127.0.0.1:8000/storage/products/banner2.jpg',
                'url' => '/new-arrivals',
                'button_text' => 'View Collection',
                'order' => 2,
                'active' => true
            ],
            [
                'title' => 'Holiday Sale',
                'description' => 'Up to 50% off on selected items',
                'image' => '127.0.0.1:8000/storage/products/banner3.webp',
                'url' => '/sale',
                'button_text' => 'Shop Sale',
                'order' => 3,
                'active' => true
            ]
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }
    }
}
