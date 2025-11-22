<?php

namespace App\Services;

use App\Models\BillingStatement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate invoice PDF for a billing statement.
     */
    public function generateInvoice(BillingStatement $billingStatement): string
    {
        // Create or get existing invoice
        $invoice = $billingStatement->invoice ?? Invoice::create([
            'billing_statement_id' => $billingStatement->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now(),
            'amount' => $billingStatement->amount,
            'status' => 'SENT',
        ]);

        // Generate PDF if not already generated
        if (!$invoice->pdf_path || !Storage::exists($invoice->pdf_path)) {
            $html = view('invoices.template', [
                'invoice' => $invoice,
                'billingStatement' => $billingStatement,
                'user' => $billingStatement->user,
                'subscription' => $billingStatement->membershipSubscription,
            ])->render();

            $dompdf = new Dompdf();
            $dompdf->getOptions()->setChroot(public_path());
            $dompdf->getOptions()->setIsRemoteEnabled(true);
            $dompdf->getOptions()->setIsHtml5ParserEnabled(true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'invoices/invoice-' . $invoice->invoice_number . '-' . now()->format('Y-m-d') . '.pdf';
            Storage::put($filename, $dompdf->output());

            $invoice->pdf_path = $filename;
            $invoice->save();
        }

        return $invoice->pdf_path;
    }

    /**
     * Generate receipt PDF for a payment.
     */
    public function generateReceipt(Payment $payment): string
    {
        // Create or get existing receipt
        $receipt = $payment->receipt ?? Receipt::create([
            'payment_id' => $payment->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $payment->payment_date ?? now(),
            'amount' => $payment->amount,
        ]);

        // Generate PDF if not already generated
        if (!$receipt->pdf_path || !Storage::exists($receipt->pdf_path)) {
            $html = view('receipts.template', [
                'receipt' => $receipt,
                'payment' => $payment,
                'user' => $payment->user,
            ])->render();

            $dompdf = new Dompdf();
            $dompdf->getOptions()->setChroot(public_path());
            $dompdf->getOptions()->setIsRemoteEnabled(true);
            $dompdf->getOptions()->setIsHtml5ParserEnabled(true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'receipts/receipt-' . $receipt->receipt_number . '-' . now()->format('Y-m-d') . '.pdf';
            Storage::put($filename, $dompdf->output());

            $receipt->pdf_path = $filename;
            $receipt->save();
        }

        return $receipt->pdf_path;
    }

    /**
     * Get PDF file path for download.
     * Generates invoice if it doesn't exist.
     */
    public function getInvoicePdfPath(BillingStatement $billingStatement): ?string
    {
        try {
            // Always ensure invoice exists - generate if needed
            if (!$billingStatement->invoice) {
                $this->generateInvoice($billingStatement);
                $billingStatement->refresh();
            }

            // If PDF doesn't exist, regenerate it
            if (!$billingStatement->invoice->pdf_path || !Storage::exists($billingStatement->invoice->pdf_path)) {
                $this->generateInvoice($billingStatement);
                $billingStatement->refresh();
            }

            return $billingStatement->invoice->pdf_path;
        } catch (\Exception $e) {
            \Log::error('Failed to get invoice PDF path', [
                'billing_statement_id' => $billingStatement->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get PDF file path for download.
     */
    public function getReceiptPdfPath(Payment $payment): ?string
    {
        if (!$payment->receipt) {
            $this->generateReceipt($payment);
            $payment->refresh();
        }

        return $payment->receipt->pdf_path;
    }
}

