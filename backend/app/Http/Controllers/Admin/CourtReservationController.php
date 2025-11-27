<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourtReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourtReservationController extends Controller
{
    /**
     * List all reservations with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CourtReservation::with(['user']);

        // Filter by date
        if ($request->has('date')) {
            $query->forDate($request->date);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('reservation_date', [$request->start_date, $request->end_date]);
        }

        // Filter by court number
        if ($request->has('court_number')) {
            $query->forCourt($request->court_number);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by member (search by name or email)
        if ($request->has('member_search')) {
            $search = $request->member_search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort by date and time
        $query->orderBy('reservation_date', 'asc')
              ->orderBy('start_time', 'asc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $reservations = $query->paginate($perPage);

        return response()->json($reservations);
    }

    /**
     * Get reservation details.
     */
    public function show($id): JsonResponse
    {
        $reservation = CourtReservation::with(['user'])->findOrFail($id);

        return response()->json([
            'id' => $reservation->id,
            'user' => [
                'id' => $reservation->user->id,
                'name' => $reservation->user->name,
                'email' => $reservation->user->email,
            ],
            'court_number' => $reservation->court_number,
            'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
            'start_time' => $reservation->start_time->format('Y-m-d H:i:s'),
            'end_time' => $reservation->end_time->format('Y-m-d H:i:s'),
            'duration_minutes' => $reservation->duration_minutes,
            'status' => $reservation->status,
            'cancelled_at' => $reservation->cancelled_at ? $reservation->cancelled_at->format('Y-m-d H:i:s') : null,
            'cancellation_reason' => $reservation->cancellation_reason,
            'created_at' => $reservation->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Cancel a reservation (admin override).
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reservation = CourtReservation::findOrFail($id);

        if ($reservation->status === 'CANCELLED') {
            return response()->json([
                'message' => 'Reservation is already cancelled.',
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
}

