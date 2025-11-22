<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class MembershipSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'payment_id',
        'membership_offer_id',
        'promo_id',
        'first_time_discount_id',
        'price_paid',
        'start_date',
        'end_date',
        'status',
        'is_recurring',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Validate on saving
        static::saving(function ($subscription) {
            // Validate start_date < end_date if both are present
            if ($subscription->start_date && $subscription->end_date) {
                if ($subscription->start_date->gte($subscription->end_date)) {
                    throw new \InvalidArgumentException('Start date must be before end date.');
                }
            }

            // Validate end_date is not in the past for new ACTIVE subscriptions
            if ($subscription->status === 'ACTIVE' && $subscription->end_date && $subscription->end_date->isPast()) {
                // Auto-update to EXPIRED if end_date has passed
                if ($subscription->exists) {
                    $subscription->status = 'EXPIRED';
                } else {
                    throw new \InvalidArgumentException('Cannot create active subscription with past end date.');
                }
            }

            // Validate status is valid
            $validStatuses = ['ACTIVE', 'EXPIRED', 'CANCELLED'];
            if ($subscription->status && !in_array($subscription->status, $validStatuses)) {
                throw new \InvalidArgumentException("Status must be one of: " . implode(', ', $validStatuses));
            }

            // Validate price_paid >= 0
            if ($subscription->price_paid !== null && $subscription->price_paid < 0) {
                throw new \InvalidArgumentException('Price paid cannot be negative.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipOffer(): BelongsTo
    {
        return $this->belongsTo(MembershipOffer::class);
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function firstTimeDiscount(): BelongsTo
    {
        return $this->belongsTo(FirstTimeDiscount::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function billingStatements()
    {
        return $this->hasMany(BillingStatement::class);
    }

    /**
     * Check if subscription is due for renewal (expires within 5 days).
     */
    public function isDueForRenewal(): bool
    {
        // Null checks
        if (!$this->status || !$this->end_date) {
            return false;
        }

        if ($this->status !== 'ACTIVE' || !$this->is_recurring) {
            return false;
        }

        // Ensure end_date is a Carbon instance
        if (!$this->end_date instanceof \Carbon\Carbon) {
            return false;
        }

        $fiveDaysFromNow = now()->addDays(5);
        return $this->end_date->lte($fiveDaysFromNow) && $this->end_date->gte(now());
    }

    /**
     * Get next billing date.
     */
    public function getNextBillingDate()
    {
        // Null checks
        if (!$this->is_recurring || !$this->end_date) {
            return null;
        }

        // Ensure end_date is a Carbon instance
        if (!$this->end_date instanceof \Carbon\Carbon) {
            return null;
        }

        return $this->end_date;
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiration(): ?int
    {
        // Null checks
        if (!$this->end_date) {
            return null;
        }

        // Ensure end_date is a Carbon instance
        if (!$this->end_date instanceof \Carbon\Carbon) {
            return null;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Update subscription status to EXPIRED if end_date has passed.
     * Returns true if status was updated, false otherwise.
     */
    public function updateExpiredStatus(): bool
    {
        // Null checks
        if (!$this->status || !$this->end_date) {
            return false;
        }

        // Ensure end_date is a Carbon instance
        if (!$this->end_date instanceof \Carbon\Carbon) {
            return false;
        }

        // Only update if currently ACTIVE and end_date has passed
        if ($this->status === 'ACTIVE' && $this->end_date->isPast()) {
            $this->status = 'EXPIRED';
            return $this->save();
        }

        return false;
    }

    /**
     * Scope to get expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'EXPIRED')
            ->orWhere(function ($q) {
                $q->where('status', 'ACTIVE')
                  ->whereNotNull('end_date')
                  ->where('end_date', '<', now());
            });
    }

    /**
     * Scope to get subscriptions that need to be marked as expired.
     */
    public function scopeNeedsExpirationUpdate($query)
    {
        return $query->where('status', 'ACTIVE')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }

    /**
     * Static method to update all subscriptions that should be marked as expired.
     * Returns count of updated subscriptions.
     */
    public static function updateExpiredSubscriptions(): int
    {
        $subscriptions = self::needsExpirationUpdate()->get();
        $updatedCount = 0;

        foreach ($subscriptions as $subscription) {
            if ($subscription->updateExpiredStatus()) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }
}
