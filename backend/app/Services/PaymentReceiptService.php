<?php

namespace App\Services;

use App\Models\Payment;
use App\Notifications\PaymentReceiptNotification;
use Illuminate\Support\Facades\Log;

class PaymentReceiptService
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    /**
     * Ensure a receipt exists and email it to the member.
     */
    public function deliver(Payment $payment): void
    {
        try {
            $payment->loadMissing(['user', 'membershipOffer', 'receipt', 'subscription']);

            if (!$payment->user) {
                Log::warning('PaymentReceiptService: Payment has no user, skipping receipt email', [
                    'payment_id' => $payment->id,
                ]);
                return;
            }

            $pdfPath = $this->invoiceService->getReceiptPdfPath($payment);

            $payment->user->notify(new PaymentReceiptNotification($payment, $pdfPath));
        } catch (\Throwable $e) {
            Log::error('PaymentReceiptService: Failed to send receipt email', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


