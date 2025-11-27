<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtReservationRequest extends FormRequest
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
        $minDuration = \App\Models\FacilitySetting::getMinReservationDuration();
        $advanceBookingDays = \App\Models\FacilitySetting::getAdvanceBookingDays();

        return [
            'reservation_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays($advanceBookingDays)->format('Y-m-d')],
            'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'duration_minutes' => ['required', 'integer', 'min:' . $minDuration, 'multiple_of:30'],
            'court_number' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $minDuration = \App\Models\FacilitySetting::getMinReservationDuration();
        $advanceBookingDays = \App\Models\FacilitySetting::getAdvanceBookingDays();

        return [
            'reservation_date.after_or_equal' => 'Reservation date cannot be in the past.',
            'reservation_date.before_or_equal' => "Reservations can only be made up to {$advanceBookingDays} days in advance.",
            'duration_minutes.min' => "Minimum reservation duration is {$minDuration} minutes.",
            'duration_minutes.multiple_of' => 'Reservation duration must be in 30-minute increments.',
        ];
    }
}

