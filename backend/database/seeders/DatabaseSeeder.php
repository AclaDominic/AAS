<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user (not in members table)
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create member user (in members table)
        $member = User::create([
            'name' => 'Member User',
            'email' => 'member@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Add member to members table
        Member::create([
            'user_id' => $member->id,
        ]);

        // Seed membership offers, promos, and first-time discounts
        $this->call([
            MembershipOfferSeeder::class,
            PromoSeeder::class,
            FirstTimeDiscountSeeder::class,
            FacilityScheduleSeeder::class,
            FacilitySettingSeeder::class,
        ]);

        // Seed test data for new features (billing, subscriptions, payments, etc.)
        // Comment out this line if you don't want test data
        $this->call([
            TestDataSeeder::class,
        ]);
    }
}
