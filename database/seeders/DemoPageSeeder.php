<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class DemoPageSeeder extends Seeder
{
    public function run(): void
    {
        // Check if demo page already exists
        if (Page::where('slug', 'demo')->exists()) {
            echo "Demo page already exists!\n";
            return;
        }

        // Create demo page
        $page = Page::create([
            'slug' => 'demo',
            'title' => 'Demo Page - Section Builder',
            'meta_description' => 'Demonstration of the section-based page builder system',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Hero Section
        $page->sections()->create([
            'section_type' => 'hero',
            'order' => 0,
            'content' => [
                'title' => 'Welcome to the Page Builder Demo',
                'subtitle' => 'Dynamic pages built with reusable section components',
                'ctaText' => 'View product',
                'ctaLink' => '/product',
                'backgroundImage' => '',
            ],
            'settings' => [
                'height' => 'full',
                'textAlign' => 'center',
                'overlay' => false,
            ],
        ]);

        // Features Section
        $page->sections()->create([
            'section_type' => 'features',
            'order' => 1,
            'content' => [
                'title' => 'Key Features',
                'subtitle' => 'What makes this page builder special',
                'features' => [
                    [
                        'icon' => 'check',
                        'title' => 'Dynamic Rendering',
                        'description' => 'Pages are rendered from database JSON in real-time',
                    ],
                    [
                        'icon' => 'star',
                        'title' => '7 Section Types',
                        'description' => 'Hero, Features, Gallery, CTA, Testimonials, Contact, and Content sections',
                    ],
                    [
                        'icon' => 'fast',
                        'title' => 'API-Driven',
                        'description' => 'Full REST API for creating and managing pages programmatically',
                    ],
                ],
            ],
            'settings' => [
                'columns' => 3,
                'layout' => 'grid',
                'backgroundColor' => '#F9FAFB',
            ],
        ]);

        // CTA Section
        $page->sections()->create([
            'section_type' => 'cta',
            'order' => 2,
            'content' => [
                'title' => 'Ready to Build Your Own Pages?',
                'description' => 'Use our API to create custom pages with flexible section layouts',
                'buttonText' => 'View Documentation',
                'buttonLink' => '/',
            ],
            'settings' => [
                'backgroundColor' => '#4F46E5',
                'textColor' => '#FFFFFF',
            ],
        ]);

        echo "Demo page created successfully!\n";
        echo "Visit: http://localhost:8000/pages/demo\n";
    }
}
