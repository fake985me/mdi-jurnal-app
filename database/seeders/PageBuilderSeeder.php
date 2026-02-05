<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PageTemplate;

class PageBuilderSeeder extends Seeder
{
    public function run(): void
    {
        // Landing Page Template
        PageTemplate::create([
            'name' => 'Landing Page',
            'description' => 'Full-featured landing page with hero, features, CTA, and contact sections',
            'is_active' => true,
            'default_sections' => [
                [
                    'type' => 'hero',
                    'content' => [
                        'title' => 'Welcome to Our Site',
                        'subtitle' => 'Discover amazing products and services',
                        'ctaText' => 'Get Started',
                        'ctaLink' => '/product',
                        'backgroundImage' => '/assets/hero-bg.jpg',
                    ],
                    'settings' => [
                        'height' => 'full',
                        'textAlign' => 'center',
                        'overlay' => true,
                        'overlayOpacity' => 0.5,
                    ],
                ],
                [
                    'type' => 'features',
                    'content' => [
                        'title' => 'Our Features',
                        'subtitle' => 'What makes us different',
                        'features' => [
                            [
                                'icon' => 'check',
                                'title' => 'Quality Products',
                                'description' => 'Premium quality networking equipment',
                            ],
                            [
                                'icon' => 'support',
                                'title' => '24/7 Support',
                                'description' => 'Round the clock customer support',
                            ],
                            [
                                'icon' => 'delivery',
                                'title' => 'Fast Delivery',
                                'description' => 'Quick delivery to your location',
                            ],
                        ],
                    ],
                    'settings' => [
                        'columns' => 3,
                        'layout' => 'grid',
                    ],
                ],
                [
                    'type' => 'cta',
                    'content' => [
                        'title' => 'Ready to Get Started?',
                        'description' => 'Contact us today for a free consultation',
                        'buttonText' => 'Contact Us',
                        'buttonLink' => '/contact',
                    ],
                    'settings' => [
                        'backgroundColor' => '#4F46E5',
                        'textColor' => '#FFFFFF',
                    ],
                ],
                [
                    'type' => 'contact',
                    'content' => [
                        'title' => 'Get In Touch',
                        'email' => 'info@example.com',
                        'phone' => '+62 123 456 7890',
                        'address' => 'Jakarta, Indonesia',
                    ],
                    'settings' => [],
                ],
            ],
        ]);

        // About Page Template
        PageTemplate::create([
            'name' => 'About Us',
            'description' => 'Company information and team showcase',
            'is_active' => true,
            'default_sections' => [
                [
                    'type' => 'hero',
                    'content' => [
                        'title' => 'About Our Company',
                        'subtitle' => 'Learn more about who we are',
                        'backgroundImage' => '/assets/about-bg.jpg',
                    ],
                    'settings' => [
                        'height' => 'md',
                        'textAlign' => 'center',
                    ],
                ],
                [
                    'type' => 'content',
                    'content' => [
                        'title' => 'Our Story',
                        'body' => 'We are a leading provider of networking solutions...',
                        'image' => '/assets/company.jpg',
                    ],
                    'settings' => [
                        'layout' => 'image-left',
                    ],
                ],
                [
                    'type' => 'testimonials',
                    'content' => [
                        'title' => 'What Our Clients Say',
                        'testimonials' => [
                            [
                                'name' => 'John Doe',
                                'company' => 'Tech Corp',
                                'quote' => 'Excellent service and quality products!',
                                'avatar' => '/assets/avatar1.jpg',
                            ],
                        ],
                    ],
                    'settings' => [],
                ],
            ],
        ]);

        // Services Page Template
        PageTemplate::create([
            'name' => 'Services',
            'description' => 'Showcase your services and solutions',
            'is_active' => true,
            'default_sections' => [
                [
                    'type' => 'hero',
                    'content' => [
                        'title' => 'Our Services',
                        'subtitle' => 'Comprehensive networking solutions',
                    ],
                    'settings' => [
                        'height' => 'sm',
                    ],
                ],
                [
                    'type' => 'features',
                    'content' => [
                        'title' => 'What We Offer',
                        'features' => [
                            [
                                'title' => 'Training',
                                'description' => 'Professional GPON training programs',
                            ],
                            [
                                'title' => 'Maintenance',
                                'description' => 'On-site and remote support services',
                            ],
                            [
                                'title' => 'Installation',
                                'description' => 'Network design and installation',
                            ],
                        ],
                    ],
                    'settings' => [
                        'columns' => 3,
                    ],
                ],
            ],
        ]);

        // Blank Template
        PageTemplate::create([
            'name' => 'Blank Page',
            'description' => 'Start from scratch with an empty page',
            'is_active' => true,
            'default_sections' => [],
        ]);

        echo "Page Builder templates seeded successfully!\n";
    }
}
