<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Solution;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\ContactInfo;

class CmsContentSeeder extends Seeder
{
    public function run(): void
    {
        // Solutions from useSolutions.js
        $solutions = [
            [
                'title' => 'Training',
                'description' => 'This training is designed to provide a comprehensive understanding of GPON (Gigabit Passive Optical Network) technology, covering basic theory, network architecture, device introduction, to installation and configuration practices. GPON is a modern fiber optic network solution that is widely used by internet service providers (ISPs), telecommunications operators, and enterprises.',
                'image_url' => '/assets/static/solutions/training.jpg',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Maintenance and Support Services',
                'description' => 'With proper maintenance and taking preventive measures to avoid future network issues, such as regular maintenance and security audits, we can ensure smooth operations, minimize downtime, increase productivity, and protect data from the risk of loss or security breaches.',
                'image_url' => '/assets/static/solutions/Remote.jpg',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Managed Services',
                'description' => 'We provide services and set clear SLAs, as appropriate and effective solutions, optimizing the use of IT resources, to improve the operational efficiency of your business.',
                'image_url' => '/assets/static/solutions/managed.png',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Design and Build',
                'description' => 'Our team of experts will help handle the process from design, consultation and installation, according to what you want.',
                'image_url' => '/assets/static/solutions/cable.jpg',
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($solutions as $solution) {
            Solution::create($solution);
        }

        // Sample Projects
        $projects = [
            [
                'title' => 'Enterprise Network Infrastructure',
                'description' => 'Complete GPON network deployment for enterprise client',
                'image_url' => '/assets/static/projects/project1.jpg',
                'category' => 'Infrastructure',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'ISP Fiber Network',
                'description' => 'Large scale fiber optic network for internet service provider',
                'image_url' => '/assets/static/projects/project2.jpg',
                'category' => 'ISP',
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($projects as $project) {
            Project::create($project);
        }

        // Site Settings
        $settings = [
            ['key' => 'site_name', 'value' => 'Warehouse App', 'group' => 'general', 'type' => 'text', 'label' => 'Site Name'],
            ['key' => 'site_tagline', 'value' => 'Your Network Solutions Partner', 'group' => 'general', 'type' => 'text', 'label' => 'Site Tagline'],
            ['key' => 'hero_title', 'value' => 'Professional Network Solutions', 'group' => 'landing', 'type' => 'text', 'label' => 'Hero Title'],
            ['key' => 'hero_subtitle', 'value' => 'Expert GPON, Fiber Optic & Wireless Solutions', 'group' => 'landing', 'type' => 'textarea', 'label' => 'Hero Subtitle'],
            ['key' => 'about_title', 'value' => 'About Us', 'group' => 'landing', 'type' => 'text', 'label' => 'About Title'],
            ['key' => 'about_description', 'value' => 'We are a leading provider of network solutions specializing in GPON, fiber optic, and wireless technology.', 'group' => 'landing', 'type' => 'textarea', 'label' => 'About Description'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::create($setting);
        }

        // Contact Information
        $contacts = [
            ['type' => 'email', 'label' => 'Email', 'value' => 'info@warehouse-app.com', 'icon' => 'envelope', 'order' => 1, 'is_active' => true],
            ['type' => 'phone', 'label' => 'Phone', 'value' => '+62 123 456 7890', 'icon' => 'phone', 'order' => 2, 'is_active' => true],
            ['type' => 'address', 'label' => 'Address', 'value' => 'Jakarta, Indonesia', 'icon' => 'map-marker', 'order' => 3, 'is_active' => true],
            ['type' => 'social', 'label' => 'LinkedIn', 'value' => 'https://linkedin.com/company/warehouse-app', 'icon' => 'linkedin', 'order' => 4, 'is_active' => true],
        ];

        foreach ($contacts as $contact) {
            ContactInfo::create($contact);
        }

        echo "CMS Content seeded successfully!\n";
    }
}
