<?php

namespace Database\Seeders;

use App\Models\Promo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 2 promos
        Promo::create([
            'name' => 'Summer Sale - 20% Off',
            'description' => 'Get 20% off on all memberships this summer!',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 20.00,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(30),
            'is_active' => true,
            'applicable_to_category' => 'ALL',
        ]);

        Promo::create([
            'name' => 'GYM Special - $10 Off',
            'description' => 'Special $10 discount for gym memberships only',
            'discount_type' => 'FIXED_AMOUNT',
            'discount_value' => 10.00,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(15),
            'is_active' => true,
            'applicable_to_category' => 'GYM',
        ]);
    }
}
