<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourtReservationRequest;
use App\Models\CourtReservation;
use App\Services\ReservationService;
use App\Services\TimeSlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourtReservationController extends Controller
{
    protected $reservationService;
    protected $timeSlotService;

    public function __construct(ReservationService $reservationService, TimeSlotService $timeSlotService)
    {
        $this->reservationService = $reservationService;
        $this->timeSlotService = $timeSlotService;
    }

    /**
     * Get member's reservations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = CourtReservation::where('user_id', $user->id);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'upcoming') {
                $query->upcoming();
            } elseif ($request->status === 'past') {
                $query->where('start_time', '<', now());
            } elseif ($request->status === 'cancelled') {
                $query->cancelled();
            }
        }

        // Sort by date and time
        $query->orderBy('start_time', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $reservations = $query->paginate($perPage);

        return response()->json($reservations);
    }

    /**
     * Get available time slots for a given date.
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);
        $slots = $this->timeSlotService->getAvailableSlots($date);

        // Get member's reservations for this date to highlight them
        $user = $request->user();
        $memberReservations = CourtReservation::where('user_id', $user->id)
            ->forDate($date)
            ->active()
            ->get();

        $memberReservationTimes = [];
        foreach ($memberReservations as $reservation) {
            $memberReservationTimes[] = [
                'start' => $reservation->start_time->format('H:i'),
                'end' => $reservation->end_time->format('H:i'),
                'court_number' => $reservation->court_number,
            ];
        }

        // Format slots for frontend
        $formattedSlots = array_map(function ($slot) use ($date) {
            return [
                'start_time' => $date->format('Y-m-d') . ' ' . $slot['time_string'] . ':00',
                'time_string' => $slot['time_string'],
                'available_courts' => $slot['available_courts'],
                'is_available' => $slot['is_available'],
                'booked_courts' => $slot['booked_courts'],
            ];
        }, $slots);

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'slots' => $formattedSlots,
            'member_reservations' => $memberReservationTimes,
        ]);
    }

    /**
     * Create new reservation.
     */
    public function store(StoreCourtReservationRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $date = Carbon::parse($request->reservation_date);
            $startTime = Carbon::parse($request->start_time);
            $durationMinutes = $request->duration_minutes;
            $courtNumber = $request->court_number ?? null;

            $reservation = $this->reservationService->createReservation(
                $user->id,
                $date,
                $startTime,
                $durationMinutes,
                $courtNumber
            );

            return response()->json([
                'message' => 'Reservation created successfully.',
                'reservation' => [
                    'id' => $reservation->id,
                    'court_number' => $reservation->court_number,
                    'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
                    'start_time' => $reservation->start_time->format('Y-m-d H:i:s'),
                    'end_time' => $reservation->end_time->format('Y-m-d H:i:s'),
                    'duration_minutes' => $reservation->duration_minutes,
                    'status' => $reservation->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create reservation.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get reservation details.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $reservation = CourtReservation::where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'id' => $reservation->id,
            'court_number' => $reservation->court_number,
            'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
            'start_time' => $reservation->start_time->format('Y-m-d H:i:s'),
            'end_time' => $reservation->end_time->format('Y-m-d H:i:s'),
            'duration_minutes' => $reservation->duration_minutes,
            'status' => $reservation->status,
            'cancelled_at' => $reservation->cancelled_at ? $reservation->cancelled_at->format('Y-m-d H:i:s') : null,
            'cancellation_reason' => $reservation->cancellation_reason,
        ]);
    }

    /**
     * Get reservation duration options for a selected start slot.
     */
    public function getReservationOptions(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $date = Carbon::parse($request->date);
        $startTime = Carbon::parse($request->start_time);

        $options = $this->timeSlotService->calculateReservationOptions($date, $startTime);

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'options' => $options,
        ]);
    }

    /**
     * Cancel own reservation.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $reservation = CourtReservation::where('user_id', $user->id)
            ->findOrFail($id);

        if ($reservation->status === 'CANCELLED') {
            return response()->json([
                'message' => 'Reservation is already cancelled.',
            ], 400);
        }

        if ($reservation->status === 'COMPLETED') {
            return response()->json([
                'message' => 'Cannot cancel a completed reservation.',
            ], 400);
        }

        $reservation->cancel($request->reason);

        return response()->json([
            'message' => 'Reservation cancelled successfully.',
            'reservation' => [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'cancelled_at' => $reservation->cancelled_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Check if specific slot is available.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'duration_minutes' => 'required|integer',
            'court_number' => 'nullable|integer',
        ]);

        $date = Carbon::parse($request->date);
        $startTime = Carbon::parse($request->start_time);
        $durationMinutes = $request->duration_minutes;
        $courtNumber = $request->court_number ?? null;

        // Validate reservation
        $validationErrors = $this->timeSlotService->validateReservation($date, $startTime, $durationMinutes);
        if (!empty($validationErrors)) {
            return response()->json([
                'available' => false,
                'errors' => $validationErrors,
            ], 400);
        }

        $endTime = $startTime->copy()->addMinutes($durationMinutes);
        $availableCourt = $this->reservationService->findAvailableCourt($date, $startTime, $endTime, $courtNumber);

        return response()->json([
            'available' => $availableCourt !== null,
            'court_number' => $availableCourt,
        ]);
    }
}

