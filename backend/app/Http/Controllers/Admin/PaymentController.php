<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\UserPromoUsage;
use App\Models\UserDiscountUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Find payment by code.
     */
    public function findByCode(Request $request, string $code): JsonResponse
    {
        $payment = Payment::where('payment_code', strtoupper($code))
            ->with(['user', 'membershipOffer', 'promo', 'firstTimeDiscount'])
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found with the provided code.',
            ], 404);
        }

        return response()->json($payment);
    }

    /**
     * Mark payment as paid and create subscription.
     */
    public function markAsPaid(Request $request, $id): JsonResponse
    {
        $request->validate([
            'payment_date' => ['nullable', 'date'],
        ]);

        $payment = Payment::with(['user', 'membershipOffer', 'promo', 'firstTimeDiscount'])
            ->findOrFail($id);

        if ($payment->status !== 'PENDING') {
            return response()->json([
                'message' => 'Only pending payments can be marked as paid.',
            ], 400);
        }

        if ($payment->payment_method !== 'CASH') {
            return response()->json([
                'message' => 'Only cash payments can be marked as paid by admin.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Mark payment as paid and clear payment code
            $payment->status = 'PAID';
            $payment->payment_date = $request->payment_date ? \Carbon\Carbon::parse($request->payment_date) : now();
            $payment->payment_code = null; // Clear payment code when marked as paid
            $payment->save();

            // Create subscription
            $user = $payment->user;
            $membershipOffer = $payment->membershipOffer;
            $startDate = $payment->payment_date->copy();
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
                'promo_id' => $payment->promo_id,
                'first_time_discount_id' => $payment->first_time_discount_id,
                'price_paid' => $payment->amount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'ACTIVE',
                'is_recurring' => $isRecurring,
            ]);

            // Record promo usage
            if ($payment->promo) {
                UserPromoUsage::firstOrCreate([
                    'user_id' => $user->id,
                    'promo_id' => $payment->promo_id,
                ], [
                    'used_at' => $payment->payment_date,
                ]);
            }

            // Record first-time discount usage
            if ($payment->firstTimeDiscount) {
                UserDiscountUsage::firstOrCreate([
                    'user_id' => $user->id,
                    'first_time_discount_id' => $payment->first_time_discount_id,
                ], [
                    'used_at' => $payment->payment_date,
                ]);
            }

            DB::commit();

            $payment->load(['membershipOffer', 'promo', 'firstTimeDiscount', 'subscription']);

            return response()->json([
                'message' => 'Payment marked as paid and subscription created successfully.',
                'payment' => $payment,
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
