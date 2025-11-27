<?php

namespace Database\Seeders;

use App\Models\FacilitySchedule;
use Illuminate\Database\Seeder;

class FacilityScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            ['day_of_week' => 0, 'day_name' => 'Sunday', 'open_time' => '08:00:00', 'close_time' => '22:00:00', 'is_open' => true],
            ['day_of_week' => 1, 'day_name' => 'Monday', 'open_time' => '08:00:00', 'close_time' => '22:00:00', 'is_open' => true],
            ['day_of_week' => 2, 'day_name' => 'Tuesday', 'open_time' => '08:00:00', 'close_time' => '22:00:00', 'is_open' => true],
            ['day_of_week' => 3, 'day_name' => 'Wednesday', 'open_time' => '08:00:00', 'close_time' => '22:00:00', 'is_open' => true],
            ['day_of_week' => 4, 'day_name' => 'Thursday', 'open_time' => '08:00:00', 'close_time' => '22:00:00', 'is_open' => true],
            ['day_of_week' => 5, 'day_name' => 'Friday', 'open_time' => '08:00:00', 'close_time' => '22:00:00', 'is_open' => true],
            ['day_of_week' => 6, 'day_name' => 'Saturday', 'open_time' => '08:00:00', 'close_time' => '22:00:00', 'is_open' => true],
        ];

        foreach ($days as $day) {
            FacilitySchedule::updateOrCreate(
                ['day_of_week' => $day['day_of_week']],
                [
                    'open_time' => $day['open_time'],
                    'close_time' => $day['close_time'],
                    'is_open' => $day['is_open'],
                ]
            );
        }
    }
}

