<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingStatement;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Notifications\BillingStatementGenerated;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BillingController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Generate billing statements for a period.
     */
    public function generateStatements(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $fiveDaysFromNow = now()->addDays(5);

        // Find all active recurring subscriptions that:
        // 1. Have end_date within the specified date range OR
        // 2. Have end_date within 5 days from now (for renewal period)
        $subscriptions = MembershipSubscription::where('status', 'ACTIVE')
            ->where('is_recurring', true)
            ->where(function ($query) use ($startDate, $endDate, $fiveDaysFromNow) {
                $query->whereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($fiveDaysFromNow) {
                        $q->where('end_date', '<=', $fiveDaysFromNow)
                          ->where('end_date', '>=', now());
                    });
            })
            ->with(['user', 'membershipOffer'])
            ->get();

        $generated = [];
        $totalAmount = 0;
        $userIds = [];
        $errors = [];

        foreach ($subscriptions as $subscription) {
            // Skip if subscription doesn't have required data
            if (!$subscription->user || !$subscription->membershipOffer || !$subscription->end_date) {
                $errors[] = "Subscription ID {$subscription->id} is missing required data (user, offer, or end_date)";
                continue;
            }

            // Check if billing statement already exists for this subscription (avoid duplicates)
            $existingStatement = BillingStatement::where('membership_subscription_id', $subscription->id)
                ->where('status', 'PENDING')
                ->whereBetween('statement_date', [$startDate->copy()->subDays(7), $endDate->copy()->addDays(7)])
                ->first();

            if ($existingStatement) {
                continue; // Skip if statement already exists
            }

            try {
                DB::beginTransaction();

                // Calculate period dates
                $periodStart = $subscription->end_date->copy();
                $periodEnd = $subscription->end_date->copy();
                
                // Calculate next period end based on offer duration
                if ($subscription->membershipOffer->duration_type === 'MONTH') {
                    $periodEnd->addMonths($subscription->membershipOffer->duration_value);
                } else {
                    $periodEnd->addYears($subscription->membershipOffer->duration_value);
                }

                // Create billing statement
                $billingStatement = BillingStatement::create([
                    'user_id' => $subscription->user_id,
                    'membership_subscription_id' => $subscription->id,
                    'statement_date' => now(),
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'amount' => $subscription->membershipOffer->price,
                    'status' => 'PENDING',
                    'due_date' => $subscription->end_date,
                ]);

                // Create pending payment with payment code
                $payment = Payment::create([
                    'user_id' => $subscription->user_id,
                    'membership_offer_id' => $subscription->membership_offer_id,
                    'payment_method' => 'CASH', // Default, user can change to online
                    'amount' => $subscription->membershipOffer->price,
                    'status' => 'PENDING',
                    'payment_code' => Payment::generatePaymentCode(),
                ]);

                // Link payment to billing statement
                $billingStatement->payment_id = $payment->id;
                $billingStatement->save();

                // Generate invoice using InvoiceService
                try {
                    $this->invoiceService->generateInvoice($billingStatement);
                } catch (\Exception $e) {
                    Log::warning('Failed to generate invoice for billing statement ' . $billingStatement->id, [
                        'error' => $e->getMessage(),
                    ]);
                    // Continue even if invoice generation fails
                }

                DB::commit();

                // Send notification to user
                try {
                    $subscription->user->notify(new BillingStatementGenerated($billingStatement));
                } catch (\Exception $e) {
                    Log::warning('Failed to send notification for billing statement ' . $billingStatement->id, [
                        'error' => $e->getMessage(),
                    ]);
                    // Continue even if notification fails
                }

                $generated[] = [
                    'billing_statement_id' => $billingStatement->id,
                    'user_id' => $subscription->user_id,
                    'user_name' => $subscription->user->name,
                    'subscription_id' => $subscription->id,
                    'amount' => $billingStatement->amount,
                ];

                $totalAmount += $billingStatement->amount;
                if (!in_array($subscription->user_id, $userIds)) {
                    $userIds[] = $subscription->user_id;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $errorMsg = "Failed to generate billing statement for subscription {$subscription->id}: " . $e->getMessage();
                $errors[] = $errorMsg;
                Log::error($errorMsg, [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Billing statement generation completed.',
            'summary' => [
                'statements_generated' => count($generated),
                'total_amount' => $totalAmount,
                'users_affected' => count($userIds),
                'user_ids' => $userIds,
            ],
            'generated_statements' => $generated,
            'errors' => $errors,
        ]);
    }

    /**
     * List billing statements.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BillingStatement::with(['user', 'membershipSubscription.membershipOffer', 'payment']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('statement_date', [$request->start_date, $request->end_date]);
        }

        $statements = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($statements);
    }

    /**
     * Get statement details.
     */
    public function show($id): JsonResponse
    {
        $statement = BillingStatement::with([
            'user',
            'membershipSubscription.membershipOffer',
            'payment',
            'invoice',
        ])->findOrFail($id);

        return response()->json($statement);
    }

    /**
     * Generate/download invoice PDF.
     */
    public function downloadInvoice($id)
    {
        $statement = BillingStatement::with(['user', 'membershipSubscription.membershipOffer', 'invoice'])
            ->findOrFail($id);

        $pdfPath = $this->invoiceService->getInvoicePdfPath($statement);

        if (!$pdfPath || !Storage::exists($pdfPath)) {
            return response()->json(['message' => 'Invoice PDF not found.'], 404);
        }

        return Storage::download($pdfPath, 'invoice-' . $statement->invoice->invoice_number . '.pdf');
    }

    /**
     * Generate/download receipt PDF.
     */
    public function downloadReceipt($id)
    {
        $payment = Payment::with(['user', 'membershipOffer', 'promo', 'firstTimeDiscount'])
            ->findOrFail($id);

        if ($payment->status !== 'PAID') {
            return response()->json(['message' => 'Receipt can only be generated for paid payments.'], 400);
        }

        $pdfPath = $this->invoiceService->getReceiptPdfPath($payment);

        if (!$pdfPath || !Storage::exists($pdfPath)) {
            return response()->json(['message' => 'Receipt PDF not found.'], 404);
        }

        return Storage::download($pdfPath, 'receipt-' . $payment->receipt->receipt_number . '.pdf');
    }
}

