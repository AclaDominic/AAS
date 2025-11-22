<?php

namespace App\Notifications;

use App\Models\BillingStatement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillingStatementGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $billingStatement;

    /**
     * Create a new notification instance.
     */
    public function __construct(BillingStatement $billingStatement)
    {
        $this->billingStatement = $billingStatement;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subscription = $this->billingStatement->membershipSubscription;
        $offer = $subscription->membershipOffer;

        return (new MailMessage)
            ->subject('Membership Renewal Notice - Payment Due Soon')
            ->line('Your ' . $offer->name . ' membership is expiring soon.')
            ->line('Expiration Date: ' . $this->billingStatement->due_date->format('F d, Y'))
            ->line('Amount Due: ₱' . number_format($this->billingStatement->amount, 2))
            ->line('Please renew your membership to continue enjoying our services.')
            ->action('View Billing', url('/billing'))
            ->line('Thank you for being a valued member!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'billing_statement_id' => $this->billingStatement->id,
            'amount' => $this->billingStatement->amount,
            'due_date' => $this->billingStatement->due_date,
        ];
    }
}

