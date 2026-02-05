<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'title' => 'Telkom Indonesia FTTH Deployment',
                'description' => 'Successfully deployed fiber optic network infrastructure for residential areas covering 5000+ homes in Jakarta and surrounding areas.',
                'image_url' => '/assets/static/projects/telkom.jpg',
                'url' => null,
                'category' => 'FTTH',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'XL Axiata Network Upgrade',
                'description' => 'Upgraded existing network infrastructure with latest DASAN OLT equipment, improving network capacity and reliability for 10,000+ subscribers.',
                'image_url' => '/assets/static/projects/xl.jpg',
                'url' => null,
                'category' => 'Network Upgrade',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Smart City Network - Surabaya',
                'description' => 'Implemented high-speed fiber network infrastructure to support smart city initiatives including traffic management, CCTV, and IoT devices.',
                'image_url' => '/assets/static/projects/smartcity.jpg',
                'url' => null,
                'category' => 'Smart City',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Industrial Park Connectivity',
                'description' => 'Provided complete network solution for industrial park in Karawang, connecting 50+ factories with high-speed fiber optic network.',
                'image_url' => '/assets/static/projects/industrial.jpg',
                'url' => null,
                'category' => 'Enterprise',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'University Campus Network',
                'description' => 'Deployed campus-wide fiber network covering dormitories, lecture halls, and administrative buildings serving 15,000+ students.',
                'image_url' => '/assets/static/projects/campus.jpg',
                'url' => null,
                'category' => 'Education',
                'order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($projects as $project) {
            Project::create($project);
        }
    }
}
