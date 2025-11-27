<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFacilitySettingRequest;
use App\Models\FacilitySetting;
use Illuminate\Http\JsonResponse;

class FacilitySettingController extends Controller
{
    /**
     * Get current settings.
     */
    public function show(): JsonResponse
    {
        $settings = FacilitySetting::getInstance();

        return response()->json([
            'id' => $settings->id,
            'number_of_courts' => $settings->number_of_courts,
            'minimum_reservation_duration_minutes' => $settings->minimum_reservation_duration_minutes,
            'advance_booking_days' => $settings->advance_booking_days,
        ]);
    }

    /**
     * Update settings.
     */
    public function update(UpdateFacilitySettingRequest $request): JsonResponse
    {
        $settings = FacilitySetting::getInstance();
        $settings->update($request->validated());

        return response()->json([
            'message' => 'Facility settings updated successfully.',
            'settings' => [
                'id' => $settings->id,
                'number_of_courts' => $settings->number_of_courts,
                'minimum_reservation_duration_minutes' => $settings->minimum_reservation_duration_minutes,
                'advance_booking_days' => $settings->advance_booking_days,
            ],
        ]);
    }
}

