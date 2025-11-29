<?php

namespace App\Services;

use App\Models\CourtReservation;
use App\Models\FacilitySetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService
{
    protected $timeSlotService;

    public function __construct(TimeSlotService $timeSlotService)
    {
        $this->timeSlotService = $timeSlotService;
    }

    /**
     * Check if member has active membership for the specified category.
     */
    public function checkMemberEligibility(int $userId, string $category): bool
    {
        $user = User::find($userId);
        
        if (!$user || !$user->isMember()) {
            return false;
        }

        // Check for active membership in the specified category
        $activeSubscriptions = $user->getActiveSubscriptions();
        
        foreach ($activeSubscriptions as $subscription) {
            if ($subscription->membershipOffer && $subscription->membershipOffer->category === $category) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if member has overlapping reservations.
     */
    public function checkOverlap(int $userId, Carbon $startTime, Carbon $endTime, ?int $excludeReservationId = null): bool
    {
        return CourtReservation::checkOverlap($userId, $startTime, $endTime, $excludeReservationId);
    }

    /**
     * Find an available court for a time slot (only for badminton court reservations).
     */
    public function findAvailableCourt(Carbon $date, Carbon $startTime, Carbon $endTime, string $category, ?int $preferredCourt = null): ?int
    {
        // For gym reservations, court_number is not required
        if ($category === 'GYM') {
            return null;
        }

        $numberOfCourts = FacilitySetting::getNumberOfCourts();

        // If preferred court is specified, check if it's available
        if ($preferredCourt !== null) {
            if ($preferredCourt < 1 || $preferredCourt > $numberOfCourts) {
                return null;
            }

            $conflictingReservation = CourtReservation::forDate($date)
                ->forCourt($preferredCourt)
                ->where('category', 'BADMINTON_COURT')
                ->active()
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        // Overlap check: reservation starts before new end AND reservation ends after new start
                        $q->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                    });
                })
                ->first();

            if (!$conflictingReservation) {
                return $preferredCourt;
            }
        }

        // Find first available court
        for ($courtNumber = 1; $courtNumber <= $numberOfCourts; $courtNumber++) {
            $conflictingReservation = CourtReservation::forDate($date)
                ->forCourt($courtNumber)
                ->where('category', 'BADMINTON_COURT')
                ->active()
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        // Overlap check: reservation starts before new end AND reservation ends after new start
                        $q->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                    });
                })
                ->first();

            if (!$conflictingReservation) {
                return $courtNumber;
            }
        }

        return null;
    }

    /**
     * Create a reservation with conflict checking.
     */
    public function createReservation(
        int $userId,
        string $category,
        Carbon $date,
        Carbon $startTime,
        int $durationMinutes,
        ?int $courtNumber = null
    ): CourtReservation {
        // Check member eligibility
        if (!$this->checkMemberEligibility($userId, $category)) {
            $categoryName = $category === 'GYM' ? 'gym' : 'badminton court';
            throw new \Exception("Member does not have an active {$categoryName} membership.");
        }

        // Validate reservation
        $validationErrors = $this->timeSlotService->validateReservation($date, $startTime, $durationMinutes);
        if (!empty($validationErrors)) {
            throw new \Exception(implode(' ', $validationErrors));
        }

        // Calculate end time
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // Check for overlapping reservations for the same user (no overlap allowed)
        if ($this->checkOverlap($userId, $startTime, $endTime)) {
            throw new \Exception('You already have a reservation that overlaps with this time slot.');
        }

        // Find available court (only for badminton court reservations)
        $assignedCourt = $this->findAvailableCourt($date, $startTime, $endTime, $category, $courtNumber);
        
        if ($category === 'BADMINTON_COURT' && $assignedCourt === null) {
            throw new \Exception('No courts available for the selected time slot.');
        }

        // Create reservation
        try {
            DB::beginTransaction();

            $reservation = CourtReservation::create([
                'user_id' => $userId,
                'category' => $category,
                'court_number' => $assignedCourt,
                'reservation_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $durationMinutes,
                'status' => 'PENDING',
            ]);

            DB::commit();

            return $reservation;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create reservation', [
                'user_id' => $userId,
                'category' => $category,
                'date' => $date,
                'start_time' => $startTime,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

