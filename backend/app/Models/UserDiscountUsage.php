<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDiscountUsage extends Model
{
    protected $table = 'user_discount_usage';

    protected $fillable = [
        'user_id',
        'first_time_discount_id',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firstTimeDiscount(): BelongsTo
    {
        return $this->belongsTo(FirstTimeDiscount::class);
    }
}
