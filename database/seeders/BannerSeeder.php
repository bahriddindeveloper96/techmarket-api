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
                'image' => 'https://eragon.uz/storage/products/banner1.jpg',
                'url' => '/special-offers',
                'button_text' => 'Shop Now',
                'order' => 1,
                'active' => true
            ],
            [
                'title' => 'New Collection',
                'description' => 'Check out our new arrivals',
                'image' => 'https://eragon.uz/storage/products/banner2.jpg',
                'url' => '/new-arrivals',
                'button_text' => 'View Collection',
                'order' => 2,
                'active' => true
            ],
            [
                'title' => 'Holiday Sale',
                'description' => 'Up to 50% off on selected items',
                'image' => 'https://eragon.uz/storage/products/banner3.jpg',
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
