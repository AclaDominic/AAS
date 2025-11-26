<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StorePaymentRequest;
use App\Models\BillingStatement;
use App\Models\FirstTimeDiscount;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\Promo;
use App\Models\UserPromoUsage;
use App\Models\UserDiscountUsage;
use App\Services\InvoiceService;
use App\Services\MayaPaymentService;
use App\Services\PaymentReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            // Generate payment code for all payment methods
            $paymentCode = Payment::generatePaymentCode();

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

            // For online payments, immediately create Maya checkout session
            $checkoutUrl = null;
            if (in_array($request->payment_method, ['ONLINE_MAYA', 'ONLINE_CARD'])) {
                $mayaService = new MayaPaymentService();
                $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
                
                // Split user name into first and last name
                $nameParts = explode(' ', $user->name, 2);
                $firstName = $nameParts[0] ?? $user->name;
                $lastName = $nameParts[1] ?? '';

                // Map payment method to Maya preference - both go to card payment
                $paymentMethodPreference = 'CARD';

                // Create Maya checkout session
                $checkoutData = $mayaService->createCheckout([
                    'amount' => $finalPrice,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'phone' => '',
                    'item_name' => $membershipOffer->name,
                    'success_url' => $frontendUrl . '/payment/success?payment_id=' . $payment->id,
                    'failure_url' => $frontendUrl . '/payment/failure?payment_id=' . $payment->id,
                    'cancel_url' => $frontendUrl . '/payment/cancel?payment_id=' . $payment->id,
                    'request_reference_number' => 'PAY-' . $payment->id . '-' . time(),
                    'payment_method_preference' => $paymentMethodPreference,
                ]);

                if ($checkoutData['success']) {
                    // Verify we have the required checkout data
                    if (empty($checkoutData['checkout_id']) || empty($checkoutData['redirect_url'])) {
                        DB::rollBack();
                        Log::error('Maya checkout created but missing required data', [
                            'payment_id' => $payment->id ?? null,
                            'payment_method' => $request->payment_method,
                            'checkout_id' => $checkoutData['checkout_id'] ?? null,
                            'redirect_url' => $checkoutData['redirect_url'] ?? null,
                        ]);
                        return response()->json([
                            'message' => 'Failed to initialize payment: Invalid response from payment gateway.',
                        ], 500);
                    }

                    // Store checkout ID
                    $payment->maya_checkout_id = $checkoutData['checkout_id'];
                    $payment->save();
                    $checkoutUrl = $checkoutData['redirect_url'];
                } else {
                    DB::rollBack();
                    Log::error('Maya checkout creation failed in initiatePurchase', [
                        'payment_id' => $payment->id ?? null,
                        'payment_method' => $request->payment_method,
                        'payment_method_preference' => $paymentMethodPreference ?? null,
                        'error' => $checkoutData['error'] ?? 'Unknown error',
                        'details' => $checkoutData['details'] ?? null,
                    ]);
                    return response()->json([
                        'message' => 'Failed to initialize payment: ' . ($checkoutData['error'] ?? 'Unknown error'),
                        'details' => config('app.debug') ? ($checkoutData['details'] ?? null) : null,
                    ], 400);
                }
            }

            DB::commit();

            $payment->load(['membershipOffer', 'promo', 'firstTimeDiscount']);

            $response = [
                'message' => $request->payment_method === 'CASH' 
                    ? 'Payment initiated. Please bring the payment code to complete your payment.'
                    : 'Payment initiated. Redirecting to payment gateway...',
                'payment' => $payment,
            ];

            // Include checkout URL for online payments
            if ($checkoutUrl) {
                $response['checkout_url'] = $checkoutUrl;
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initiation failed', [
                'user_id' => $user->id,
                'offer_id' => $request->membership_offer_id,
                'payment_method' => $request->payment_method ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to initiate payment. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your payment.',
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
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found or you do not have permission to access it.',
            ], 404);
        }

        if ($payment->status !== 'PENDING') {
            return response()->json([
                'message' => "Cannot cancel payment. Payment status is {$payment->status}.",
            ], 400);
        }

        $payment->status = 'CANCELLED';
        $payment->payment_code = null; // Clear payment code when cancelled
        $payment->save();

        return response()->json([
            'message' => 'Payment cancelled successfully.',
        ]);
    }

    /**
     * Process online payment via Maya checkout.
     */
    public function processOnlinePayment(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $payment = Payment::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['membershipOffer', 'promo', 'firstTimeDiscount'])
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found or you do not have permission to access it.',
            ], 404);
        }

        if ($payment->status !== 'PENDING') {
            return response()->json([
                'message' => "This payment is already {$payment->status}. Cannot process online payment.",
            ], 400);
        }

        // Allow any pending payment to be processed online (supports change of mind from walk-in to online)
        try {
            DB::beginTransaction();

            $mayaService = new MayaPaymentService();
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
            
            // Split user name into first and last name
            $nameParts = explode(' ', $user->name, 2);
            $firstName = $nameParts[0] ?? $user->name;
            $lastName = $nameParts[1] ?? '';

            // Always use CARD payment method (Maya Wallet option removed)
            $paymentMethodPreference = 'CARD';

            // Create Maya checkout session
            $checkoutData = $mayaService->createCheckout([
                'amount' => $payment->amount,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'phone' => '',
                'item_name' => $payment->membershipOffer?->name ?? 'Membership Payment',
                'success_url' => $frontendUrl . '/payment/success?payment_id=' . $payment->id,
                'failure_url' => $frontendUrl . '/payment/failure?payment_id=' . $payment->id,
                'cancel_url' => $frontendUrl . '/payment/cancel?payment_id=' . $payment->id,
                'request_reference_number' => 'PAY-' . $payment->id . '-' . time(),
                'payment_method_preference' => $paymentMethodPreference,
            ]);

            if (!$checkoutData['success']) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to initialize payment: ' . ($checkoutData['error'] ?? 'Unknown error'),
                ], 400);
            }

            // Store checkout ID
            $payment->maya_checkout_id = $checkoutData['checkout_id'];
            $payment->save();

            DB::commit();

            return response()->json([
                'message' => 'Payment checkout created. Please complete payment.',
                'checkout_url' => $checkoutData['redirect_url'],
                'payment' => $payment->load(['membershipOffer', 'promo', 'firstTimeDiscount']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing failed', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Payment processing failed. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Maya payment callback/webhook.
     */
    public function handleMayaCallback(Request $request): JsonResponse
    {
        $request->validate([
            'checkoutId' => 'required|string',
            'paymentId' => 'nullable|string',
        ]);

        $mayaService = new MayaPaymentService();
        $verification = $mayaService->verifyPayment($request->checkoutId);

        if (!$verification['success']) {
            return response()->json([
                'message' => 'Payment verification failed',
            ], 400);
        }

        $payment = Payment::where('maya_checkout_id', $request->checkoutId)->first();

        if (!$payment) {
            Log::warning('Maya callback: Payment not found', [
                'checkout_id' => $request->checkoutId,
            ]);
            return response()->json([
                'message' => 'Payment not found for this checkout ID.',
            ], 404);
        }

        // If payment is already processed, return success
        if ($payment->status === 'PAID') {
            Log::info('Maya callback: Payment already processed', [
                'payment_id' => $payment->id,
                'checkout_id' => $request->checkoutId,
            ]);
            return response()->json([
                'message' => 'Payment already processed.',
                'payment' => $payment->load(['membershipOffer', 'promo', 'firstTimeDiscount', 'receipt']),
            ]);
        }

        // Only process if payment is still pending
        if ($payment->status !== 'PENDING') {
            Log::warning('Maya callback: Payment not in PENDING status', [
                'payment_id' => $payment->id,
                'checkout_id' => $request->checkoutId,
                'current_status' => $payment->status,
            ]);
            return response()->json([
                'message' => 'Payment is not in a processable state.',
                'current_status' => $payment->status,
            ], 400);
        }

        try {
            DB::beginTransaction();

            if ($verification['status'] === 'PAYMENT_SUCCESS') {
                $payment->status = 'PAID';
                $payment->payment_date = now();
                $payment->maya_payment_id = $verification['payment_id'];
                $payment->maya_payment_token = $verification['payment_token'] ?? null;
                $payment->maya_metadata = $verification['data'];
                $payment->save();

                // Create subscription
                $membershipOffer = $payment->membershipOffer;
                $this->createSubscription($payment, $payment->user, $membershipOffer, $payment->promo, $payment->firstTimeDiscount, $payment->amount);

                // Generate receipt
                $invoiceService = new InvoiceService();
                $invoiceService->generateReceipt($payment);

                DB::commit();

                app(PaymentReceiptService::class)->deliver($payment);

                return response()->json([
                    'message' => 'Payment processed successfully.',
                    'payment' => $payment->load(['membershipOffer', 'promo', 'firstTimeDiscount', 'receipt']),
                ]);
            } else {
                $payment->status = 'FAILED';
                $payment->maya_metadata = $verification['data'];
                $payment->save();

                DB::commit();

                return response()->json([
                    'message' => 'Payment failed.',
                    'payment' => $payment,
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Maya callback processing failed', [
                'checkout_id' => $request->checkoutId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Payment processing failed.',
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

        // Generate receipt if payment is PAID
        if ($payment->status === 'PAID') {
            $invoiceService = new InvoiceService();
            $invoiceService->generateReceipt($payment);
        }

        return $subscription;
    }

    /**
     * Process bulk payment for multiple pending renewals.
     */
    public function bulkPay(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_ids' => ['required', 'array', 'min:1'],
            'subscription_ids.*' => ['exists:membership_subscriptions,id'],
            'payment_method' => ['required', 'in:ONLINE_MAYA,ONLINE_CARD'],
        ]);

        $user = $request->user();
        $subscriptionIds = $request->subscription_ids;

        // Verify all subscriptions belong to the user and are due for renewal
        $subscriptions = MembershipSubscription::where('user_id', $user->id)
            ->whereIn('id', $subscriptionIds)
            ->where('status', 'ACTIVE')
            ->where('is_recurring', true)
            ->with('membershipOffer')
            ->get();

        if ($subscriptions->count() !== count($subscriptionIds)) {
            return response()->json([
                'message' => 'Some subscriptions are invalid or not eligible for renewal.',
            ], 400);
        }

        // Calculate total amount
        $totalAmount = $subscriptions->sum(function ($subscription) {
            return $subscription->membershipOffer->price;
        });

        try {
            DB::beginTransaction();

            $payments = [];
            foreach ($subscriptions as $subscription) {
                    // Find or create billing statement
                    $billingStatement = BillingStatement::where('membership_subscription_id', $subscription->id)
                    ->where('status', 'PENDING')
                    ->first();

                if (!$billingStatement) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Billing statement not found for one or more subscriptions.',
                    ], 400);
                }

                // Create payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'membership_offer_id' => $subscription->membership_offer_id,
                    'payment_method' => $request->payment_method,
                    'amount' => $subscription->membershipOffer->price,
                    'status' => 'PAID', // For online payments, mark as paid immediately
                    'payment_date' => now(),
                ]);

                // Link payment to billing statement
                $billingStatement->payment_id = $payment->id;
                $billingStatement->status = 'PAID';
                $billingStatement->save();

                // Create subscription renewal
                $this->createSubscription($payment, $user, $subscription->membershipOffer, null, null, $payment->amount);

                $payments[] = $payment;
            }

            DB::commit();

            $receiptService = app(PaymentReceiptService::class);
            foreach ($payments as $payment) {
                $receiptService->deliver($payment);
            }

            return response()->json([
                'message' => 'Bulk payment processed successfully!',
                'payments' => $payments,
                'total_amount' => $totalAmount,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process bulk payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
