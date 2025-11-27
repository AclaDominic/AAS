<?php

namespace Database\Seeders;

use App\Models\FacilitySetting;
use Illuminate\Database\Seeder;

class FacilitySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default facility settings (singleton pattern)
        FacilitySetting::updateOrCreate(
            ['id' => 1],
            [
                'number_of_courts' => 2,
                'minimum_reservation_duration_minutes' => 30,
                'advance_booking_days' => 30,
            ]
        );
    }
}

