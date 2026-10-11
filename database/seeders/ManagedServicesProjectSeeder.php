<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ManagedServicesProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            [
                'title' => 'Moratelindo',
                'description' => 'Moratelindo is an Indonesian telecommunications company that provides a range of telecommunications and network services. We signed and implemented FTTH Project for Moratelindo at 2015',
                'image_url' => '/assets/static/project/moratel.jpg',
                'url' => '#',
                'category' => 'ISP Customer',
                'order' => 1,
            ],
            [
                'title' => 'Indonet',
                'description' => 'Founded in 1994, Indonet is a pioneer in the provision of digital infrastructure services. offering various solutions and services designed to meet the evolving needs of modern businesses. We signed and implemented for FTTH Project',
                'image_url' => '/assets/static/project/indonet.png',
                'url' => '#',
                'category' => 'ISP Customer',
                'order' => 2,
            ],
            [
                'title' => 'Pelindo Place Office Tower 1',
                'description' => 'Jl. Perak Timur No.478, Perak Utara, Kec. Pabean Cantikan, Surabaya, Jawa Timur 60165',
                'image_url' => '/assets/static/project/pelindo.jpg',
                'category' => 'FTTH',
                'order' => 1,
            ],
            [
                'title' => 'Sopo Del Office Tower',
                'description' => 'Jl. Mega Kuningan Barat III, Lot 10. 1-6. Kawasan Mega Kuningan. Jakarta 12950.',
                'image_url' => '/assets/static/project/sopodel.jpg',
                'category' => 'FTTx Project',
                'order' => 2,
            ],
            [
                'title' => 'Capitol Suites',
                'description' => 'Jl.kko usman dan harun kavling 22-24, RT.1/RW.5, Kwitang, Kec. Senen, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10410',
                'image_url' => '/assets/static/project/capitol.jpg',
                'category' => 'FTTx Project',
                'order' => 3,
            ],
            [
                'title' => 'Padma Hotel Semarang',
                'description' => 'Jl. Sultan Agung No.86, Wonotingal, Kec. Candisari, Kota Semarang, Jawa Tengah 50252',
                'image_url' => '/assets/static/project/padma.png',
                'category' => 'FTTx Project',
                'order' => 4,
            ],
            [
                'title' => 'Patra Cirebon Hotel & Convention',
                'description' => 'Jl. Tuparev No.11, Kedawung, Kec. Kedawung, Kabupaten Cirebon, Jawa Barat 45153',
                'image_url' => '/assets/static/project/patracirebon.png',
                'category' => 'FTTx Project',
                'order' => 5,
            ],
            [
                'title' => 'Nihi Sumba Island',
                'description' => 'Nihi Sumba Island, Desa Hobawawi, Kecamatan Wanukaka, Kabupaten Sumba Barat Daya, Nusa Tenggara Timur 87254',
                'image_url' => '/assets/static/project/nihisumba.jpg',
                'category' => 'FTTx Project',
                'order' => 6,
            ],
            [
                'title' => 'Kemenko 4 IKN',
                'description' => 'Jl. Sumbu sisi Barat, Kecamatan Sepaku, Kabupaten Penajam Paser Utara, Kalimantan Timur 76200',
                'image_url' => '/assets/static/project/kemenko.jpg',
                'category' => 'FTTx Project',
                'order' => 7,
            ],
            [
                'title' => 'Rusun ASN 2 IKN',
                'description' => 'Pemaluan, Sepaku, Penajam North Paser Regency, East Kalimantan 76147',
                'image_url' => '/assets/static/project/asn2.jpg',
                'category' => 'FTTx Project',
                'order' => 8,
            ],
            [
                'title' => 'Rusun ASN 3 IKN',
                'description' => 'Pemaluan, Sepaku, Penajam North Paser Regency, East Kalimantan 76147',
                'image_url' => '/assets/static/project/asn3.jpg',
                'category' => 'FTTx Project',
                'order' => 9,
            ],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(
                ['title' => $project['title'], 'category' => $project['category']],
                array_merge($project, ['is_active' => true])
            );
        }

        $projects = [
            [
                'title' => 'Menara Danareksa',
                'description' => 'Jl. Medan Merdeka Sel. No.14, Gambir, Jakarta Pusat, DKI Jakarta 10110',
                'image_url' => '/assets/static/project/menaradanareksa.jpg',
                'order' => 1,
            ],
            [
                'title' => 'Taman Melati Margonda',
                'description' => 'Jl. Margonda Raya No.525A, Pondok Cina, Beji, Kota Depok, Jawa Barat 16424',
                'image_url' => '/assets/static/project/tamanmelatidepok.jpg',
                'order' => 2,
            ],
            [
                'title' => 'Taman Melati Merr Surabaya',
                'description' => 'Jl. Mulyorejo Utara No.201, Mulyorejo, Kota SBY, Jawa Timur 60115',
                'image_url' => '/assets/static/project/tamanmelatisurabaya.jpg',
                'order' => 3,
            ],
            [
                'title' => 'Pollux Meisterstadt Batam',
                'description' => 'Jl. Ahmad Yani, Simpang Franky, Batam Kota, Kepulauan Riau 29463',
                'image_url' => '/assets/static/project/pollux.jpg',
                'order' => 4,
            ],
            [
                'title' => 'Lampung City Mall',
                'description' => 'Jl. Yos Sudarso No.80, Bumi Waras, Kota Bandar Lampung, Lampung 35225',
                'image_url' => '/assets/static/project/lampung.jpg',
                'order' => 5,
            ],
            [
                'title' => 'Chadstone Cikarang',
                'description' => 'Jalan Raya Cikarang - Cibarusah, Exit Toll KM31 Cikarang Barat, Kabupaten Bekasi, Jawa Barat 17530',
                'image_url' => '/assets/static/project/chadstone-building-.jpg',
                'order' => 6,
            ],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(
                ['title' => $project['title'], 'category' => 'Managed Services'],
                array_merge($project, ['category' => 'Managed Services', 'is_active' => true])
            );
        }
    }
}
