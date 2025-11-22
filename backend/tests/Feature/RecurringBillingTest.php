<?php

namespace Tests\Feature;

use App\Jobs\ProcessRecurringBilling;
use App\Models\BillingStatement;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecurringBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_billing_job_creates_billing_statements(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);
        
        $offer = MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Test Offer',
            'description' => 'Test',
            'price' => 1000,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);
        $subscription = MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(3), // Expires in 3 days
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        // Run the job
        $job = new ProcessRecurringBilling();
        $job->handle();

        // Check that billing statement was created
        $this->assertDatabaseHas('billing_statements', [
            'user_id' => $user->id,
            'membership_subscription_id' => $subscription->id,
            'status' => 'PENDING',
        ]);

        // Check that payment was created
        $statement = BillingStatement::where('membership_subscription_id', $subscription->id)->first();
        $this->assertNotNull($statement->payment_id);
        $this->assertDatabaseHas('payments', [
            'id' => $statement->payment_id,
            'user_id' => $user->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_recurring_billing_does_not_create_duplicate_statements(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);
        
        $offer = MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Test Offer',
            'description' => 'Test',
            'price' => 1000,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);
        $subscription = MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(3),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        // Run job first time
        $job = new ProcessRecurringBilling();
        $job->handle();

        $firstCount = BillingStatement::where('membership_subscription_id', $subscription->id)
            ->where('status', 'PENDING')
            ->count();

        // Run job second time
        $job->handle();

        $secondCount = BillingStatement::where('membership_subscription_id', $subscription->id)
            ->where('status', 'PENDING')
            ->count();

        // Should not create duplicate
        $this->assertEquals($firstCount, $secondCount);
    }

    public function test_recurring_billing_only_processes_expiring_subscriptions(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);
        
        $offer = MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Test Offer',
            'description' => 'Test',
            'price' => 1000,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);
        
        // Subscription expiring in 3 days (should be processed)
        $expiringSubscription = MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(3),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        // Subscription expiring in 10 days (should NOT be processed)
        $futureSubscription = MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now(),
            'end_date' => now()->addDays(10),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        $job = new ProcessRecurringBilling();
        $job->handle();

        // Only expiring subscription should have billing statement
        $this->assertDatabaseHas('billing_statements', [
            'membership_subscription_id' => $expiringSubscription->id,
        ]);

        $this->assertDatabaseMissing('billing_statements', [
            'membership_subscription_id' => $futureSubscription->id,
        ]);
    }

    public function test_recurring_billing_only_processes_recurring_subscriptions(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);
        
        $offer = MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Test Offer',
            'description' => 'Test',
            'price' => 1000,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);
        
        // Recurring subscription (should be processed)
        $recurringSubscription = MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(3),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        // Non-recurring subscription (should NOT be processed)
        $nonRecurringSubscription = MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(3),
            'status' => 'ACTIVE',
            'is_recurring' => false,
        ]);

        $job = new ProcessRecurringBilling();
        $job->handle();

        // Only recurring subscription should have billing statement
        $this->assertDatabaseHas('billing_statements', [
            'membership_subscription_id' => $recurringSubscription->id,
        ]);

        $this->assertDatabaseMissing('billing_statements', [
            'membership_subscription_id' => $nonRecurringSubscription->id,
        ]);
    }
}

