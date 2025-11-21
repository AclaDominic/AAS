<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'membership_offer_id',
        'promo_id',
        'first_time_discount_id',
        'payment_code',
        'payment_method',
        'amount',
        'status',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

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

    public function subscription(): HasOne
    {
        return $this->hasOne(MembershipSubscription::class);
    }

    /**
     * Generate a unique 8-character alphanumeric payment code.
     */
    public static function generatePaymentCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('payment_code', $code)->exists());

        return $code;
    }

    /**
     * Scope to get pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope to get paid payments.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }
}
