<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacilitySettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'number_of_courts' => ['required', 'integer', 'min:1'],
            'minimum_reservation_duration_minutes' => ['required', 'integer', 'in:30,60,90,120,150,180'],
            'advance_booking_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}

