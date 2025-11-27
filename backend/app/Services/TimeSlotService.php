<?php

namespace App\Services;

use App\Models\CourtReservation;
use App\Models\FacilitySchedule;
use App\Models\FacilitySetting;
use Carbon\Carbon;

class TimeSlotService
{
    /**
     * Generate all 30-minute slots for a date based on facility schedule.
     */
    public function generateSlots(Carbon $date): array
    {
        $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
        
        $schedule = FacilitySchedule::where('day_of_week', $dayOfWeek)->first();
        
        if (!$schedule || !$schedule->is_open || !$schedule->open_time || !$schedule->close_time) {
            return [];
        }

        $slots = [];
        // Time is stored as string in format "HH:MM:SS"
        $openTimeStr = $schedule->open_time;
        $closeTimeStr = $schedule->close_time;
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $openTimeStr);
        $closeTime = Carbon::parse($date->format('Y-m-d') . ' ' . $closeTimeStr);

        $currentTime = $startTime->copy();

        while ($currentTime->lt($closeTime)) {
            $slotEnd = $currentTime->copy()->addMinutes(30);
            
            // Don't create slots that extend past closing time
            if ($slotEnd->gt($closeTime)) {
                break;
            }

            $slots[] = [
                'start_time' => $currentTime->copy(),
                'end_time' => $slotEnd->copy(),
                'time_string' => $currentTime->format('H:i'),
            ];

            $currentTime->addMinutes(30);
        }

        return $slots;
    }

    /**
     * Get available slots for a date, excluding existing reservations.
     */
    public function getAvailableSlots(Carbon $date, ?int $courtNumber = null): array
    {
        $allSlots = $this->generateSlots($date);
        $numberOfCourts = FacilitySetting::getNumberOfCourts();

        // Get all active reservations for this date
        $reservations = CourtReservation::forDate($date)
            ->active()
            ->get();

        // Mark slots as available or booked
        $availableSlots = [];
        
        foreach ($allSlots as $slot) {
            $slotStart = $slot['start_time'];
            $slotEnd = $slot['end_time'];
            
            $bookedCourts = [];
            
            // Check each reservation to see if it overlaps with this slot
            foreach ($reservations as $reservation) {
                // Check if reservation overlaps with this slot
                // Overlap: reservation starts before slot ends AND reservation ends after slot starts
                if ($reservation->start_time->lt($slotEnd) && $reservation->end_time->gt($slotStart)) {
                    if ($courtNumber === null || $reservation->court_number === $courtNumber) {
                        $bookedCourts[] = $reservation->court_number;
                    }
                }
            }

            $availableCount = $numberOfCourts - count(array_unique($bookedCourts));
            
            $availableSlots[] = [
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'time_string' => $slotStart->format('H:i'),
                'available_courts' => $availableCount,
                'is_available' => $availableCount > 0,
                'booked_courts' => array_unique($bookedCourts),
            ];
        }

        return $availableSlots;
    }

    /**
     * Calculate valid reservation durations from a start slot.
     */
    public function calculateReservationOptions(Carbon $date, Carbon $startSlot): array
    {
        $dayOfWeek = $date->dayOfWeek;
        $schedule = FacilitySchedule::where('day_of_week', $dayOfWeek)->first();
        
        if (!$schedule || !$schedule->is_open || !$schedule->close_time) {
            return [];
        }

        $minDurationMinutes = FacilitySetting::getMinReservationDuration();
        // Time is stored as string in format "HH:MM:SS"
        $closeTimeStr = $schedule->close_time;
        $closeTime = Carbon::parse($date->format('Y-m-d') . ' ' . $closeTimeStr);

        $options = [];
        $currentDuration = $minDurationMinutes;

        while (true) {
            $endTime = $startSlot->copy()->addMinutes($currentDuration);
            
            if ($endTime->gt($closeTime)) {
                break;
            }

            $hours = floor($currentDuration / 60);
            $minutes = $currentDuration % 60;
            
            $label = '';
            if ($hours > 0) {
                $label = $hours . 'hr';
                if ($minutes > 0) {
                    $label .= ' ' . $minutes . 'min';
                }
            } else {
                $label = $minutes . 'min';
            }

            $options[] = [
                'duration_minutes' => $currentDuration,
                'label' => $label,
                'end_time' => $endTime->copy(),
            ];

            $currentDuration += 30; // Increment by 30 minutes
        }

        return $options;
    }

    /**
     * Validate reservation against rules.
     */
    public function validateReservation(Carbon $date, Carbon $startTime, int $durationMinutes): array
    {
        $errors = [];

        // Check if date is within advance booking window
        $advanceBookingDays = FacilitySetting::getAdvanceBookingDays();
        $maxBookingDate = now()->addDays($advanceBookingDays);
        
        if ($date->gt($maxBookingDate)) {
            $errors[] = "Reservations can only be made up to {$advanceBookingDays} days in advance.";
        }

        // Check if date is in the past
        if ($date->lt(now()->startOfDay())) {
            $errors[] = "Cannot make reservations for past dates.";
        }

        // Check minimum duration
        $minDuration = FacilitySetting::getMinReservationDuration();
        if ($durationMinutes < $minDuration) {
            $errors[] = "Minimum reservation duration is {$minDuration} minutes.";
        }

        // Check if duration is a multiple of 30 minutes
        if ($durationMinutes % 30 !== 0) {
            $errors[] = "Reservation duration must be in 30-minute increments.";
        }

        // Check facility schedule
        $dayOfWeek = $date->dayOfWeek;
        $schedule = FacilitySchedule::where('day_of_week', $dayOfWeek)->first();
        
        if (!$schedule || !$schedule->is_open) {
            $errors[] = "Facility is closed on " . $date->format('l') . ".";
        } else {
            // open_time and close_time are stored as strings (format: "HH:MM:SS")
            $openTimeStr = $schedule->open_time;
            $closeTimeStr = $schedule->close_time;
            $openTime = Carbon::parse($date->format('Y-m-d') . ' ' . $openTimeStr);
            $closeTime = Carbon::parse($date->format('Y-m-d') . ' ' . $closeTimeStr);
            $endTime = $startTime->copy()->addMinutes($durationMinutes);

            if ($startTime->lt($openTime) || $startTime->gt($closeTime)) {
                $errors[] = "Start time is outside facility operating hours.";
            }

            if ($endTime->gt($closeTime)) {
                $errors[] = "Reservation extends past facility closing time.";
            }
        }

        return $errors;
    }
}

