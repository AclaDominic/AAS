<?php

namespace Database\Seeders;

use App\Models\FirstTimeDiscount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FirstTimeDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 2 first-time discounts
        FirstTimeDiscount::create([
            'name' => 'Welcome Discount - 15% Off',
            'description' => 'First-time members get 15% off on their first membership purchase',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 15.00,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(60),
            'is_active' => true,
            'applicable_to_category' => 'ALL',
        ]);

        FirstTimeDiscount::create([
            'name' => 'New Member Badminton Special - $20 Off',
            'description' => 'Special $20 discount for new members on badminton court memberships',
            'discount_type' => 'FIXED_AMOUNT',
            'discount_value' => 20.00,
            'start_date' => now()->subDays(7),
            'end_date' => now()->addDays(45),
            'is_active' => true,
            'applicable_to_category' => 'BADMINTON_COURT',
        ]);
    }
}
