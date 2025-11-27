<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFacilityScheduleRequest;
use App\Models\FacilitySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityScheduleController extends Controller
{
    /**
     * Get all days with schedules.
     */
    public function index(): JsonResponse
    {
        $schedules = FacilitySchedule::orderBy('day_of_week')->get();
        
        // If no schedules exist, return empty array with day structure
        if ($schedules->isEmpty()) {
            $days = [];
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            for ($i = 0; $i < 7; $i++) {
                $days[] = [
                    'day_of_week' => $i,
                    'day_name' => $dayNames[$i],
                    'open_time' => null,
                    'close_time' => null,
                    'is_open' => false,
                ];
            }
            return response()->json($days);
        }

        return response()->json($schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'day_name' => $schedule->day_name,
                'open_time' => $schedule->open_time ? substr($schedule->open_time, 0, 5) : null,
                'close_time' => $schedule->close_time ? substr($schedule->close_time, 0, 5) : null,
                'is_open' => $schedule->is_open,
            ];
        }));
    }

    /**
     * Update schedule for one or multiple days.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|between:0,6',
            'schedules.*.open_time' => 'nullable|date_format:H:i',
            'schedules.*.close_time' => 'nullable|date_format:H:i',
            'schedules.*.is_open' => 'boolean',
        ]);

        $updated = [];

        foreach ($request->schedules as $scheduleData) {
            $schedule = FacilitySchedule::updateOrCreate(
                ['day_of_week' => $scheduleData['day_of_week']],
                [
                    'open_time' => $scheduleData['is_open'] ? ($scheduleData['open_time'] ?? null) : null,
                    'close_time' => $scheduleData['is_open'] ? ($scheduleData['close_time'] ?? null) : null,
                    'is_open' => $scheduleData['is_open'] ?? false,
                ]
            );

            $updated[] = [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'day_name' => $schedule->day_name,
                'open_time' => $schedule->open_time ? substr($schedule->open_time, 0, 5) : null,
                'close_time' => $schedule->close_time ? substr($schedule->close_time, 0, 5) : null,
                'is_open' => $schedule->is_open,
            ];
        }

        return response()->json([
            'message' => 'Facility schedule updated successfully.',
            'schedules' => $updated,
        ]);
    }

    /**
     * Update schedule for a single day.
     */
    public function updateDay(UpdateFacilityScheduleRequest $request, int $dayOfWeek): JsonResponse
    {
        $schedule = FacilitySchedule::updateOrCreate(
            ['day_of_week' => $dayOfWeek],
            [
                'open_time' => $request->is_open ? ($request->open_time ?? null) : null,
                'close_time' => $request->is_open ? ($request->close_time ?? null) : null,
                'is_open' => $request->is_open ?? false,
            ]
        );

        return response()->json([
            'message' => 'Schedule updated successfully.',
            'schedule' => [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'day_name' => $schedule->day_name,
                'open_time' => $schedule->open_time ? substr($schedule->open_time, 0, 5) : null,
                'close_time' => $schedule->close_time ? substr($schedule->close_time, 0, 5) : null,
                'is_open' => $schedule->is_open,
            ],
        ]);
    }
}

