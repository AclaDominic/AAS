<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StorePaymentRequest;
use App\Models\FirstTimeDiscount;
use App\Models\MembershipOffer;
use App\Models\Payment;
use App\Models\Promo;
use App\Models\UserPromoUsage;
use App\Models\UserDiscountUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Initiate a purchase and create a pending payment.
     */
    public function initiatePurchase(StorePaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $membershipOffer = MembershipOffer::findOrFail($request->membership_offer_id);

        // Check if offer is active
        if (!$membershipOffer->is_active) {
            return response()->json([
                'message' => 'This membership offer is not available.',
            ], 400);
        }

        // Check if user already has an active membership of the same category
        $existingActiveSubscription = \App\Models\MembershipSubscription::where('user_id', $user->id)
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

        // Check if user has a pending payment for the same category
        $existingPendingPayment = Payment::where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->whereHas('membershipOffer', function ($query) use ($membershipOffer) {
                $query->where('category', $membershipOffer->category);
            })
            ->first();

        if ($existingPendingPayment) {
            return response()->json([
                'message' => "You already have a pending payment for a {$membershipOffer->category} membership. Please complete or cancel that payment first.",
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

            if ($promo->applicable_to_category !== 'ALL' && 
                $promo->applicable_to_category !== $membershipOffer->category) {
                return response()->json([
                    'message' => 'This promo is not applicable to this membership category.',
                ], 400);
            }

            if ($promo->discount_type === 'PERCENTAGE') {
                $discount = ($finalPrice * $promo->discount_value) / 100;
            } else {
                $discount = $promo->discount_value;
            }
            $finalPrice = max(0, $finalPrice - $discount);
        }

        // Validate and apply first-time discount if provided
        if ($request->first_time_discount_id) {
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

            if ($firstTimeDiscount->applicable_to_category !== 'ALL' && 
                $firstTimeDiscount->applicable_to_category !== $membershipOffer->category) {
                return response()->json([
                    'message' => 'This first-time discount is not applicable to this membership category.',
                ], 400);
            }

            if ($firstTimeDiscount->discount_type === 'PERCENTAGE') {
                $discount = ($finalPrice * $firstTimeDiscount->discount_value) / 100;
            } else {
                $discount = $firstTimeDiscount->discount_value;
            }
            $finalPrice = max(0, $finalPrice - $discount);
        }

        try {
            DB::beginTransaction();

            // Generate payment code for cash payments
            $paymentCode = null;
            if ($request->payment_method === 'CASH') {
                $paymentCode = Payment::generatePaymentCode();
            }

            // Create payment
            $payment = Payment::create([
                'user_id' => $user->id,
                'membership_offer_id' => $membershipOffer->id,
                'promo_id' => $promo?->id,
                'first_time_discount_id' => $firstTimeDiscount?->id,
                'payment_code' => $paymentCode,
                'payment_method' => $request->payment_method,
                'amount' => $finalPrice,
                'status' => 'PENDING',
            ]);

            DB::commit();

            $payment->load(['membershipOffer', 'promo', 'firstTimeDiscount']);

            return response()->json([
                'message' => $request->payment_method === 'CASH' 
                    ? 'Payment initiated. Please bring the payment code to complete your payment.'
                    : 'Payment initiated. Please complete the payment process.',
                'payment' => $payment,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to initiate payment. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all user's payments.
     */
    public function getAllPayments(Request $request): JsonResponse
    {
        $user = $request->user();
        $payments = Payment::where('user_id', $user->id)
            ->with(['membershipOffer', 'promo', 'firstTimeDiscount'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($payments);
    }

    /**
     * Get user's pending payments.
     */
    public function getPendingPayments(Request $request): JsonResponse
    {
        $user = $request->user();
        $payments = Payment::where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->with(['membershipOffer', 'promo', 'firstTimeDiscount'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($payments);
    }

    /**
     * Cancel a pending payment.
     */
    public function cancelPayment(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $payment = Payment::where('user_id', $user->id)
            ->where('id', $id)
            ->where('status', 'PENDING')
            ->firstOrFail();

        $payment->status = 'CANCELLED';
        $payment->payment_code = null; // Clear payment code when cancelled
        $payment->save();

        return response()->json([
            'message' => 'Payment cancelled successfully.',
        ]);
    }

    /**
     * Process online payment (placeholder for Maya integration).
     */
    public function processOnlinePayment(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $payment = Payment::where('user_id', $user->id)
            ->where('id', $id)
            ->where('status', 'PENDING')
            ->whereIn('payment_method', ['ONLINE_MAYA', 'ONLINE_CARD'])
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // TODO: Integrate with Maya payment gateway
            // For now, simulate successful payment
            $payment->status = 'PAID';
            $payment->payment_date = now();
            $payment->payment_code = null; // Clear payment code when marked as paid
            $payment->save();

            $membershipOffer = $payment->membershipOffer;
            $this->createSubscription($payment, $user, $membershipOffer, $payment->promo, $payment->firstTimeDiscount, $payment->amount);

            DB::commit();

            return response()->json([
                'message' => 'Payment processed successfully.',
                'payment' => $payment->load(['membershipOffer', 'promo', 'firstTimeDiscount']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Payment processing failed. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create subscription from payment.
     */
    private function createSubscription($payment, $user, $membershipOffer, $promo, $firstTimeDiscount, $finalPrice)
    {
        $startDate = \Carbon\Carbon::today();
        $endDate = null;
        $isRecurring = $membershipOffer->billing_type === 'RECURRING';

        if (!$isRecurring) {
            if ($membershipOffer->duration_type === 'MONTH') {
                // 1 month = 30 days
                $endDate = $startDate->copy()->addDays(30 * $membershipOffer->duration_value);
            } else {
                // 1 year = 365 days
                $endDate = $startDate->copy()->addDays(365 * $membershipOffer->duration_value);
            }
        }

        $subscription = \App\Models\MembershipSubscription::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
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

        return $subscription;
    }
}
