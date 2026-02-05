<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContactInfo;

class ContactInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = [
            [
                'type' => 'description',
                'label' => '',
                'value' => 'PT Moimstone Dasan Indonesia is a trusted provider of telecommunication infrastructure solutions in Indonesia, specializing in fiber optic network deployment and DASAN equipment distribution.',
                'icon' => null,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'type' => 'address',
                'label' => 'Our Address',
                'value' => "Gedung Tifa Arum Realty\n3th Floor Suite 301\nJl. Kuningan Barat. 1 No. 26\nKuningan Barat, Kec. Mampang Prapatan\nJakarta Selatan 12710",
                'icon' => 'map-marker',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'type' => 'phone',
                'label' => 'Office',
                'value' => '021 2930-6714',
                'icon' => 'phone',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'type' => 'email',
                'label' => 'Mail',
                'value' => 'info@mdi-solutions.com',
                'icon' => 'envelope',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'type' => 'sales',
                'label' => 'Sales & Marketing',
                'value' => 'Hadi : +62 887-0978-7005',
                'icon' => 'user',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'type' => 'sales',
                'label' => 'Sales & Marketing',
                'value' => 'Bambang : +62 813-1004-0018',
                'icon' => 'user',
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($contacts as $contact) {
            ContactInfo::create($contact);
        }
    }
}
