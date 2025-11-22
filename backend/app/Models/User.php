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
}
