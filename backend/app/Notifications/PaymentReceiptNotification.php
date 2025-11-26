<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class PaymentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;
    public $backoff = 600; // retry every 10 minutes

    public function __construct(private Payment $payment, private ?string $receiptPath = null)
    {
        $this->queue = 'emails';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment->loadMissing(['membershipOffer', 'receipt', 'subscription']);
        $offerName = $payment->membershipOffer?->name ?? 'your membership';
        $amount = number_format((float) $payment->amount, 2);
        $validUntil = $payment->subscription?->end_date?->format('F d, Y');

        $mail = (new MailMessage)
            ->subject('Payment Receipt')
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line("We received your payment of ₱{$amount} for {$offerName}.")
            ->line($validUntil
                ? "Your membership is active through {$validUntil}."
                : 'Your membership is now active. Keep an eye on your billing portal for renewal reminders.')
            ->line('A PDF copy of your receipt is attached for your records.')
            ->action('View Billing Portal', rtrim(config('app.frontend_url', ''), '/') . '/member/billing')
            ->line('Thank you for staying with us!');

        $receiptNumber = $payment->receipt?->receipt_number ?? $payment->id;
        if ($this->receiptPath && Storage::exists($this->receiptPath)) {
            $mail->attachData(
                Storage::get($this->receiptPath),
                'receipt-' . $receiptNumber . '.pdf',
                ['mime' => 'application/pdf']
            );
        }

        return $mail;
    }
}


