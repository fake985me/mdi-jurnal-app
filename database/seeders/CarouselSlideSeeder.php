<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarouselSlide;

class CarouselSlideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slides = [
            [
                'title' => 'MDI Solutions',
                'text' => 'Trusted distributor and Experienced technical support for DASAN equipment in Indonesia',
                'bg_image' => null,
                'center_img' => '/assets/static/carousel/mdi.png',
                'img_left' => null,
                'mid_left' => null,
                'mid_right' => null,
                'img_right' => null,
                'use_component' => true,
                'component_name' => 'LineAnimationSVG',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'DASAN Solutions',
                'text' => 'Provide conectivity solutions to your network',
                'bg_image' => '/assets/static/carousel/2.jpg',
                'center_img' => '/assets/static/carousel/dasan.png',
                'img_left' => null,
                'mid_left' => null,
                'mid_right' => null,
                'img_right' => null,
                'use_component' => false,
                'component_name' => null,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Our Products',
                'text' => 'Offers something that is beyond your reach',
                'bg_image' => '/assets/static/carousel/3.jpg',
                'center_img' => null,
                'img_left' => '/assets/static/carousel/hero_1.png',
                'mid_left' => '/assets/static/carousel/hero_2.png',
                'mid_right' => '/assets/static/carousel/hero_3.png',
                'img_right' => '/assets/static/carousel/hero_4.png',
                'use_component' => false,
                'component_name' => null,
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($slides as $slide) {
            CarouselSlide::create($slide);
        }
    }
}
