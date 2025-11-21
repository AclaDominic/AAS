<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\FirstTimeDiscount;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Promo;
use App\Models\UserDiscountUsage;
use App\Models\UserPromoUsage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipController extends Controller
{
    /**
     * Purchase a membership subscription.
     */
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'membership_offer_id' => ['required', 'exists:membership_offers,id'],
            'promo_id' => ['nullable', 'exists:promos,id'],
            'first_time_discount_id' => ['nullable', 'exists:first_time_discounts,id'],
        ]);

        $user = $request->user();
        $membershipOffer = MembershipOffer::findOrFail($request->membership_offer_id);

        // Check if offer is active
        if (!$membershipOffer->is_active) {
            return response()->json([
                'message' => 'This membership offer is not available.',
            ], 400);
        }

        // Check if user already has an active membership of the same category
        $existingActiveSubscription = MembershipSubscription::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->whereHas('membershipOffer', function ($query) use ($membershipOffer) {
                $query->where('category', $membershipOffer->category);
            })
            ->first();

        if ($existingActiveSubscription) {
            return response()->json([
                'message' => "You already have an active {$membershipOffer->category} membership. You cannot have multiple active memberships of the same category.",
            ], 400);
        }

        // Cannot use both promo and first-time discount on the same purchase
        if ($request->promo_id && $request->first_time_discount_id) {
            return response()->json([
                'message' => 'You cannot use both a promo and a first-time discount on the same membership purchase. Please choose one.',
            ], 400);
        }

        $promo = null;
        $firstTimeDiscount = null;
        $finalPrice = $membershipOffer->price;

        // Validate and apply promo if provided
        if ($request->promo_id) {
            $promo = Promo::findOrFail($request->promo_id);
            
            if (!$promo->isCurrentlyActive()) {
                return response()->json([
                    'message' => 'The selected promo is not currently active.',
                ], 400);
            }

            // Check if promo is applicable to this category
            if ($promo->applicable_to_category !== 'ALL' && 
                $promo->applicable_to_category !== $membershipOffer->category) {
                return response()->json([
                    'message' => 'This promo is not applicable to this membership category.',
                ], 400);
            }

            // Calculate discount
            if ($promo->discount_type === 'PERCENTAGE') {
                $discount = ($finalPrice * $promo->discount_value) / 100;
            } else {
                $discount = $promo->discount_value;
            }
            $finalPrice = max(0, $finalPrice - $discount);
        }

        // Validate and apply first-time discount if provided
        if ($request->first_time_discount_id) {
            // Check eligibility
            if (!$user->isEligibleForFirstTimeDiscount()) {
                return response()->json([
                    'message' => 'You are not eligible for first-time discounts. You have already used a promo or purchased a membership.',
                ], 400);
            }

            $firstTimeDiscount = FirstTimeDiscount::findOrFail($request->first_time_discount_id);
            
            if (!$firstTimeDiscount->isCurrentlyActive()) {
                return response()->json([
                    'message' => 'The selected first-time discount is not currently active.',
                ], 400);
            }

            // Check if discount is applicable to this category
            if ($firstTimeDiscount->applicable_to_category !== 'ALL' && 
                $firstTimeDiscount->applicable_to_category !== $membershipOffer->category) {
                return response()->json([
                    'message' => 'This first-time discount is not applicable to this membership category.',
                ], 400);
            }

            // Calculate discount
            if ($firstTimeDiscount->discount_type === 'PERCENTAGE') {
                $discount = ($finalPrice * $firstTimeDiscount->discount_value) / 100;
            } else {
                $discount = $firstTimeDiscount->discount_value;
            }
            $finalPrice = max(0, $finalPrice - $discount);
        }

        // Calculate start and end dates
        $startDate = Carbon::today();
        $endDate = null;
        $isRecurring = $membershipOffer->billing_type === 'RECURRING';

        if (!$isRecurring) {
            if ($membershipOffer->duration_type === 'MONTH') {
                $endDate = $startDate->copy()->addMonths($membershipOffer->duration_value);
            } else {
                $endDate = $startDate->copy()->addYears($membershipOffer->duration_value);
            }
        }

        try {
            DB::beginTransaction();

            // Create subscription
            $subscription = MembershipSubscription::create([
                'user_id' => $user->id,
                'membership_offer_id' => $membershipOffer->id,
                'promo_id' => $promo?->id,
                'first_time_discount_id' => $firstTimeDiscount?->id,
                'price_paid' => $finalPrice,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'ACTIVE',
                'is_recurring' => $isRecurring,
            ]);

            // Record promo usage
            if ($promo) {
                UserPromoUsage::create([
                    'user_id' => $user->id,
                    'promo_id' => $promo->id,
                    'used_at' => now(),
                ]);
            }

            // Record first-time discount usage
            if ($firstTimeDiscount) {
                UserDiscountUsage::create([
                    'user_id' => $user->id,
                    'first_time_discount_id' => $firstTimeDiscount->id,
                    'used_at' => now(),
                ]);
            }

            DB::commit();

            $subscription->load(['membershipOffer', 'promo', 'firstTimeDiscount']);

            return response()->json([
                'message' => 'Membership purchased successfully!',
                'subscription' => $subscription,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to purchase membership. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
