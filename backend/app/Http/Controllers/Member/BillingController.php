<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BillingStatement;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BillingController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Get user's pending renewals (subscriptions expiring in 5 days).
     */
    public function getPendingRenewals(Request $request): JsonResponse
    {
        $user = $request->user();
        $fiveDaysFromNow = now()->addDays(5);

        $subscriptions = MembershipSubscription::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->where('is_recurring', true)
            ->where('end_date', '<=', $fiveDaysFromNow)
            ->where('end_date', '>=', now())
            ->with(['membershipOffer', 'promo', 'firstTimeDiscount'])
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'membership_offer' => $subscription->membershipOffer,
                    'category' => $subscription->membershipOffer->category,
                    'name' => $subscription->membershipOffer->name,
                    'amount' => $subscription->membershipOffer->price,
                    'end_date' => $subscription->end_date,
                    'days_until_expiration' => $subscription->getDaysUntilExpiration(),
                ];
            });

        $totalAmount = $subscriptions->sum('amount');

        return response()->json([
            'subscriptions' => $subscriptions,
            'total_amount' => $totalAmount,
            'count' => $subscriptions->count(),
        ]);
    }

    /**
     * Get user's billing statements.
     */
    public function getStatements(Request $request): JsonResponse
    {
        $user = $request->user();

        $statements = BillingStatement::where('user_id', $user->id)
            ->with(['membershipSubscription.membershipOffer', 'payment', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($statements);
    }

    /**
     * Download invoice PDF.
     * Generates invoice if it doesn't exist.
     */
    public function downloadInvoice(Request $request, $id)
    {
        try {
            $user = $request->user();
            $statement = BillingStatement::where('user_id', $user->id)
                ->with(['user', 'membershipSubscription.membershipOffer', 'payment', 'invoice'])
                ->findOrFail($id);

            // Generate invoice if it doesn't exist
            $pdfPath = $this->invoiceService->getInvoicePdfPath($statement);

            if (!$pdfPath || !Storage::exists($pdfPath)) {
                return response()->json(['message' => 'Failed to generate invoice PDF.'], 500);
            }

            $invoiceNumber = $statement->invoice ? $statement->invoice->invoice_number : 'invoice-' . $statement->id;
            return Storage::download($pdfPath, 'invoice-' . $invoiceNumber . '.pdf');
        } catch (\Exception $e) {
            \Log::error('Failed to download invoice', [
                'billing_statement_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to download invoice. Please try again.'], 500);
        }
    }

    /**
     * Download receipt PDF.
     */
    public function downloadReceipt(Request $request, $id)
    {
        $user = $request->user();
        $payment = Payment::where('user_id', $user->id)
            ->with(['user', 'membershipOffer', 'promo', 'firstTimeDiscount'])
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

