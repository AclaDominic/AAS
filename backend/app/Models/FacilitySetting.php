<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilitySetting extends Model
{
    protected $fillable = [
        'number_of_courts',
        'minimum_reservation_duration_minutes',
        'advance_booking_days',
    ];

    protected $casts = [
        'number_of_courts' => 'integer',
        'minimum_reservation_duration_minutes' => 'integer',
        'advance_booking_days' => 'integer',
    ];

    /**
     * Get the singleton instance of facility settings.
     * Creates default settings if none exist.
     */
    public static function getInstance(): self
    {
        $setting = self::first();

        if (!$setting) {
            $setting = self::create([
                'number_of_courts' => 2,
                'minimum_reservation_duration_minutes' => 30,
                'advance_booking_days' => 30,
            ]);
        }

        return $setting;
    }

    /**
     * Get minimum reservation duration in minutes.
     */
    public static function getMinReservationDuration(): int
    {
        return self::getInstance()->minimum_reservation_duration_minutes;
    }

    /**
     * Get number of courts.
     */
    public static function getNumberOfCourts(): int
    {
        return self::getInstance()->number_of_courts;
    }

    /**
     * Get advance booking days limit.
     */
    public static function getAdvanceBookingDays(): int
    {
        return self::getInstance()->advance_booking_days;
    }

    /**
     * Override update method to ensure singleton pattern.
     */
    public function update(array $attributes = [], array $options = [])
    {
        // If trying to create a new record, get the existing one instead
        if (!$this->exists) {
            $existing = self::first();
            if ($existing) {
                return $existing->update($attributes, $options);
            }
        }

        return parent::update($attributes, $options);
    }
}

