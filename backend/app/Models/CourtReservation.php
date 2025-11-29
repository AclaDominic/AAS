<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtReservation extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'court_number',
        'reservation_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    protected $attributes = [
        'category' => 'BADMINTON_COURT',
        'status' => 'PENDING',
    ];

    /**
     * Get the user that owns the reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active reservations (not cancelled or completed).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['CANCELLED', 'COMPLETED']);
    }

    /**
     * Scope to get cancelled reservations.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'CANCELLED');
    }

    /**
     * Scope to get upcoming reservations.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
            ->whereNotIn('status', ['CANCELLED', 'COMPLETED']);
    }

    /**
     * Scope to filter reservations for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        if ($date instanceof Carbon) {
            $date = $date->format('Y-m-d');
        }

        return $query->whereDate('reservation_date', $date);
    }

    /**
     * Scope to filter reservations for a specific court.
     */
    public function scopeForCourt($query, $courtNumber)
    {
        return $query->where('court_number', $courtNumber);
    }

    /**
     * Cancel the reservation.
     */
    public function cancel(?string $reason = null): bool
    {
        return $this->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Check if this reservation overlaps with another reservation.
     */
    public function isOverlapping(CourtReservation $otherReservation): bool
    {
        // Same user overlap check
        if ($this->user_id !== $otherReservation->user_id) {
            return false;
        }

        // Same date
        if ($this->reservation_date->format('Y-m-d') !== $otherReservation->reservation_date->format('Y-m-d')) {
            return false;
        }

        // Time overlap check
        $thisStart = $this->start_time;
        $thisEnd = $this->end_time;
        $otherStart = $otherReservation->start_time;
        $otherEnd = $otherReservation->end_time;

        // Check if times overlap (one starts before the other ends and vice versa)
        return $thisStart->lt($otherEnd) && $thisEnd->gt($otherStart);
    }

    /**
     * Static method to check if a user has overlapping reservations.
     */
    public static function checkOverlap(int $userId, Carbon $startTime, Carbon $endTime, ?int $excludeReservationId = null): bool
    {
        $query = self::where('user_id', $userId)
            ->where('status', '!=', 'CANCELLED')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    // Reservation starts before new end and ends after new start
                    $subQ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($reservation) {
            // Ensure end_time is calculated from start_time and duration
            if ($reservation->start_time && $reservation->duration_minutes && !$reservation->end_time) {
                $reservation->end_time = $reservation->start_time->copy()->addMinutes($reservation->duration_minutes);
            }

            // Ensure reservation_date matches start_time date
            if ($reservation->start_time) {
                $reservation->reservation_date = $reservation->start_time->format('Y-m-d');
            }
        });
    }
}

