<?php

namespace App\Jobs;

use App\Models\BillingStatement;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Notifications\BillingStatementGenerated;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessRecurringBilling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fiveDaysFromNow = now()->addDays(5);

        // Find subscriptions expiring within 5 days that are recurring and active
        $subscriptions = MembershipSubscription::where('status', 'ACTIVE')
            ->where('is_recurring', true)
            ->where('end_date', '<=', $fiveDaysFromNow)
            ->where('end_date', '>=', now())
            ->with(['user', 'membershipOffer'])
            ->get();

        foreach ($subscriptions as $subscription) {
            // Check if billing statement already exists for this subscription
            $existingStatement = BillingStatement::where('membership_subscription_id', $subscription->id)
                ->where('status', 'PENDING')
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

                DB::commit();

                // Send notification to user
                $subscription->user->notify(new BillingStatementGenerated($billingStatement));
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Failed to process recurring billing for subscription ' . $subscription->id, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

