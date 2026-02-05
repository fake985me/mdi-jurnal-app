<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Solution;

class SolutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $solutions = [
            [
                'title' => 'Network Infrastructure',
                'description' => 'We provide comprehensive network infrastructure solutions including fiber optic deployment, structured cabling, and network design services to ensure reliable and high-performance connectivity for your business.',
                'image_url' => '/assets/static/solutions/network.jpg',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Fiber Optic Solutions',
                'description' => 'Specialized in FTTH (Fiber to the Home) and FTTB (Fiber to the Building) deployments using GPON and XG-PON technologies. We offer end-to-end solutions from planning to implementation.',
                'image_url' => '/assets/static/solutions/fiber.jpg',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Telecommunication Equipment',
                'description' => 'As an authorized distributor of DASAN equipment, we provide high-quality OLT, ONU, and related telecommunication devices with full technical support and warranty.',
                'image_url' => '/assets/static/solutions/telecom.jpg',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Technical Support & Maintenance',
                'description' => 'Our experienced technical team provides 24/7 support, regular maintenance, troubleshooting, and training services to ensure your network operates at peak performance.',
                'image_url' => '/assets/static/solutions/support.jpg',
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($solutions as $solution) {
            Solution::create($solution);
        }
    }
}
