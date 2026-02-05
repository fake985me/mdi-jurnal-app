<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SalesPerson;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create superadmin user (use firstOrCreate to avoid duplicates)
        User::firstOrCreate(
            ['email' => 'testmoimstone@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
            ]
        );

        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create sales user
        $salesUser = User::firstOrCreate(
            ['email' => 'sales@example.com'],
            [
                'name' => 'Sales User',
                'password' => Hash::make('password'),
                'role' => 'sales',
            ]
        );

        // Create sales person (avoid duplicates)
        SalesPerson::firstOrCreate(
            ['user_id' => $salesUser->id],
            [
                'name' => 'Sales User',
                'phone' => '08123456789',
                'email' => 'sales@example.com',
                'commission_rate' => 5.00,
                'active' => true,
            ]
        );

        // Seed categories, brands, sub-categories, and products
        $this->call([
            CategorySeeder::class,
            BrandSeeder::class,
            SubCategorySeeder::class,
            ProductsSeeder::class,
            PublicProductSeeder::class,
        ]);

        // Seed CMS content
        $this->call([
            CarouselSlideSeeder::class,
            SolutionSeeder::class,
            ProjectSeeder::class,
            ContactInfoSeeder::class,
        ]);
    }
}
