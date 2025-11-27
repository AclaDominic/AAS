<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilitySchedule extends Model
{
    protected $fillable = [
        'day_of_week',
        'open_time',
        'close_time',
        'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    /**
     * Scope to get only open days.
     */
    public function scopeOpenDays($query)
    {
        return $query->where('is_open', true)
            ->whereNotNull('open_time')
            ->whereNotNull('close_time');
    }

    /**
     * Scope to get only closed days.
     */
    public function scopeClosedDays($query)
    {
        return $query->where('is_open', false)
            ->orWhereNull('open_time')
            ->orWhereNull('close_time');
    }

    /**
     * Check if a specific day is open.
     */
    public static function isDayOpen(int $dayOfWeek): bool
    {
        $schedule = self::where('day_of_week', $dayOfWeek)->first();
        
        if (!$schedule) {
            return false;
        }

        return $schedule->is_open 
            && $schedule->open_time !== null 
            && $schedule->close_time !== null;
    }

    /**
     * Get operating hours for a specific day.
     */
    public static function getOperatingHours(int $dayOfWeek): ?array
    {
        $schedule = self::where('day_of_week', $dayOfWeek)->first();
        
        if (!$schedule || !$schedule->is_open || !$schedule->open_time || !$schedule->close_time) {
            return null;
        }

        // open_time and close_time are stored as time strings (HH:MM:SS)
        $openTime = is_string($schedule->open_time) ? $schedule->open_time : $schedule->open_time->format('H:i:s');
        $closeTime = is_string($schedule->close_time) ? $schedule->close_time : $schedule->close_time->format('H:i:s');

        return [
            'open_time' => substr($openTime, 0, 5), // Extract HH:MM
            'close_time' => substr($closeTime, 0, 5), // Extract HH:MM
        ];
    }

    /**
     * Get day name from day of week number.
     */
    public function getDayNameAttribute(): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$this->day_of_week] ?? 'Unknown';
    }
}

