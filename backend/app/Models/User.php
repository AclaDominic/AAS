<?php

namespace App\Models;

use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the member record associated with the user.
     */
    public function member()
    {
        return $this->hasOne(Member::class);
    }

    /**
     * Get the membership subscriptions for the user.
     */
    public function membershipSubscriptions()
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    /**
     * Get the promo usages for the user.
     */
    public function promoUsages()
    {
        return $this->hasMany(UserPromoUsage::class);
    }

    /**
     * Get the discount usages for the user.
     */
    public function discountUsages()
    {
        return $this->hasMany(UserDiscountUsage::class);
    }

    /**
     * Get the payments for the user.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if the user is a member.
     */
    public function isMember(): bool
    {
        return $this->member !== null;
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return !$this->isMember();
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Get the mailer that should be used for notifications.
     *
     * @return string|null
     */
    public function preferredMailer(): ?string
    {
        // Use the default mailer from config
        return config('mail.default');
    }

    /**
     * Check if the user is eligible for first-time discounts.
     * User is eligible ONLY if:
     * - They have never purchased any membership subscription
     * - They have never used any promo
     * - They have never used any first-time discount
     */
    public function isEligibleForFirstTimeDiscount(): bool
    {
        return $this->membershipSubscriptions()->count() === 0
            && $this->promoUsages()->count() === 0
            && $this->discountUsages()->count() === 0;
    }

    /**
     * Check if the user is eligible for new user promos.
     * User is eligible ONLY if:
     * - They have never purchased any membership subscription
     * - They have never used any promo
     * - They have never used any first-time discount
     */
    public function isEligibleForNewUserPromo(): bool
    {
        return $this->isEligibleForFirstTimeDiscount();
    }

    /**
     * Get all active subscriptions for the user.
     */
    public function getActiveSubscriptions()
    {
        return $this->membershipSubscriptions()
            ->where('status', 'ACTIVE')
            ->with(['membershipOffer', 'promo', 'firstTimeDiscount'])
            ->get()
            ->filter(function ($subscription) {
                // Filter out subscriptions with null membershipOffer
                return $subscription->membershipOffer !== null;
            });
    }

    /**
     * Calculate overall membership status.
     * Returns: 'active', 'inactive', or 'expired'
     */
    public function getMembershipStatus(): string
    {
        // Handle case where user has no subscriptions
        if (!$this->membershipSubscriptions()->exists()) {
            return 'inactive';
        }

        // Check for active subscriptions with valid data
        $activeSubscriptions = $this->membershipSubscriptions()
            ->where('status', 'ACTIVE')
            ->with('membershipOffer')
            ->get()
            ->filter(function ($subscription) {
                // Filter out subscriptions with null membershipOffer or invalid dates
                return $subscription->membershipOffer !== null 
                    && $subscription->end_date !== null
                    && $subscription->end_date instanceof \Carbon\Carbon;
            });

        if ($activeSubscriptions->count() > 0) {
            return 'active';
        }

        // Check for expired subscriptions
        $hasExpired = $this->membershipSubscriptions()
            ->where('status', 'EXPIRED')
            ->exists();

        return $hasExpired ? 'expired' : 'inactive';
    }

    /**
     * Calculate total amount paid by the user.
     */
    public function getTotalSpent(): float
    {
        $total = $this->payments()
            ->where('status', 'PAID')
            ->sum('amount');
        
        return $total ? (float) $total : 0.0;
    }

    /**
     * Get total amount paid.
     */
    public function getTotalPaid(): float
    {
        return $this->getTotalSpent();
    }

    /**
     * Get total amount owed (pending billing statements for subscriptions expiring in 5 days).
     */
    public function getTotalOwed(): float
    {
        // Calculate from pending billing statements if they exist
        if (class_exists(\App\Models\BillingStatement::class)) {
            $total = \App\Models\BillingStatement::where('user_id', $this->id)
                ->where('status', 'PENDING')
                ->sum('amount');
            
            if ($total) {
                return (float) $total;
            }
        }
        
        // Fallback: calculate from subscriptions expiring in 5 days
        $fiveDaysFromNow = now()->addDays(5);
        
        return $this->membershipSubscriptions()
            ->where('status', 'ACTIVE')
            ->where('is_recurring', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $fiveDaysFromNow)
            ->where('end_date', '>=', now())
            ->with('membershipOffer')
            ->get()
            ->filter(function ($subscription) {
                // Filter out subscriptions with null membershipOffer
                return $subscription->membershipOffer !== null;
            })
            ->sum(function ($subscription) {
                return $subscription->membershipOffer->price ?? 0;
            });
    }

    /**
     * Get pending renewal amount (subscriptions expiring within 5 days).
     */
    public function getPendingRenewalAmount(): float
    {
        // Null checks for subscriptions
        if (!$this->membershipSubscriptions()->exists()) {
            return 0.0;
        }

        $fiveDaysFromNow = now()->addDays(5);
        
        return $this->membershipSubscriptions()
            ->where('status', 'ACTIVE')
            ->where('is_recurring', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $fiveDaysFromNow)
            ->where('end_date', '>=', now())
            ->with('membershipOffer')
            ->get()
            ->filter(function ($subscription) {
                // Filter out subscriptions with null membershipOffer or invalid dates
                return $subscription->membershipOffer !== null
                    && $subscription->end_date !== null
                    && $subscription->end_date instanceof \Carbon\Carbon;
            })
            ->sum(function ($subscription) {
                return $subscription->membershipOffer->price ?? 0;
            });
    }

    /**
     * Get active subscriptions count by category.
     */
    public function getActiveSubscriptionsCount(): array
    {
        // Handle case where user has no subscriptions
        if (!$this->membershipSubscriptions()->exists()) {
            return [];
        }

        return $this->membershipSubscriptions()
            ->where('status', 'ACTIVE')
            ->with('membershipOffer')
            ->get()
            ->filter(function ($subscription) {
                // Filter out subscriptions with null membershipOffer or invalid dates
                return $subscription->membershipOffer !== null
                    && $subscription->end_date !== null
                    && $subscription->end_date instanceof \Carbon\Carbon;
            })
            ->groupBy(function ($subscription) {
                return $subscription->membershipOffer->category ?? 'UNKNOWN';
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();
    }

    /**
     * Scope to filter users by membership status.
     */
    public function scopeWithMembershipStatus($query, $status)
    {
        return $query->whereHas('membershipSubscriptions', function ($q) use ($status) {
            if ($status === 'active') {
                $q->where('status', 'ACTIVE');
            } elseif ($status === 'expired') {
                $q->where('status', 'EXPIRED');
            } elseif ($status === 'inactive') {
                $q->whereNotIn('status', ['ACTIVE', 'EXPIRED']);
            }
        }, $status === 'inactive' ? '=' : '>', 0);
    }

    /**
     * Scope to filter users by membership category.
     */
    public function scopeWithMembershipCategory($query, $category)
    {
        return $query->whereHas('membershipSubscriptions', function ($q) use ($category) {
            $q->where('status', 'ACTIVE')
              ->whereHas('membershipOffer', function ($offerQuery) use ($category) {
                  $offerQuery->where('category', $category);
              });
        });
    }
}
