<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BillingStatement extends Model
{
    protected $fillable = [
        'user_id',
        'membership_subscription_id',
        'statement_date',
        'period_start',
        'period_end',
        'amount',
        'status',
        'due_date',
        'payment_id',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipSubscription(): BelongsTo
    {
        return $this->belongsTo(MembershipSubscription::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}

