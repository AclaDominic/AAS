<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
