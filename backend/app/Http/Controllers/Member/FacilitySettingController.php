<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\FacilitySetting;
use Illuminate\Http\JsonResponse;

class FacilitySettingController extends Controller
{
    /**
     * Get facility settings (public read-only access for members).
     */
    public function show(): JsonResponse
    {
        $settings = FacilitySetting::getInstance();

        // Return only the settings that members need to know
        return response()->json([
            'advance_booking_days' => $settings->advance_booking_days,
            'minimum_reservation_duration_minutes' => $settings->minimum_reservation_duration_minutes,
            'number_of_courts' => $settings->number_of_courts,
        ]);
    }
}

