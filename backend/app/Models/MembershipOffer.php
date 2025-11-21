<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipOffer extends Model
{
    protected $fillable = [
        'category',
        'name',
        'description',
        'price',
        'billing_type',
        'duration_type',
        'duration_value',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'duration_value' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
