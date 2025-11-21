<?php

namespace Database\Seeders;

use App\Models\MembershipOffer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MembershipOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // GYM Offers
        MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Monthly Gym Membership',
            'description' => 'Recurring monthly gym membership with full access to all facilities',
            'price' => 49.99,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        MembershipOffer::create([
            'category' => 'GYM',
            'name' => '3-Month Gym Package',
            'description' => 'Non-recurring 3-month gym membership package - great value!',
            'price' => 129.99,
            'billing_type' => 'NON_RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 3,
            'is_active' => true,
        ]);

        // Badminton Court Offers
        MembershipOffer::create([
            'category' => 'BADMINTON_COURT',
            'name' => 'Annual Badminton Court Membership',
            'description' => 'Recurring yearly badminton court membership with unlimited court access',
            'price' => 299.99,
            'billing_type' => 'RECURRING',
            'duration_type' => 'YEAR',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        MembershipOffer::create([
            'category' => 'BADMINTON_COURT',
            'name' => '6-Month Badminton Court Package',
            'description' => 'Non-recurring 6-month badminton court membership package',
            'price' => 149.99,
            'billing_type' => 'NON_RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 6,
            'is_active' => true,
        ]);
    }
}
