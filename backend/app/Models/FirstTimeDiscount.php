<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class FirstTimeDiscount extends Model
{
    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'is_active',
        'applicable_to_category',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function userDiscountUsages(): HasMany
    {
        return $this->hasMany(UserDiscountUsage::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now());
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active 
            && Carbon::now()->between($this->start_date, $this->end_date);
    }
}
