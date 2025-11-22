<?php

namespace Tests\Feature;

use App\Models\BillingStatement;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BulkPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_process_bulk_payment_for_multiple_renewals(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $gymOffer = MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Gym Offer',
            'description' => 'Test',
            'price' => 1000,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);
        $badmintonOffer = MembershipOffer::create([
            'category' => 'BADMINTON_COURT',
            'name' => 'Badminton Offer',
            'description' => 'Test',
            'price' => 800,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        $gymSubscription = MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $gymOffer->id,
            'price_paid' => 1000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(3),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        $badmintonSubscription = MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $badmintonOffer->id,
            'price_paid' => 800,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(3),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        // Create billing statements
        $gymStatement = BillingStatement::create([
            'user_id' => $user->id,
            'membership_subscription_id' => $gymSubscription->id,
            'statement_date' => now(),
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount' => 1000,
            'status' => 'PENDING',
            'due_date' => now()->addDays(3),
        ]);

        $badmintonStatement = BillingStatement::create([
            'user_id' => $user->id,
            'membership_subscription_id' => $badmintonSubscription->id,
            'statement_date' => now(),
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount' => 800,
            'status' => 'PENDING',
            'due_date' => now()->addDays(3),
        ]);

        $response = $this->postJson('/api/payments/bulk-pay', [
            'subscription_ids' => [$gymSubscription->id, $badmintonSubscription->id],
            'payment_method' => 'ONLINE_CARD',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'payments',
                'total_amount',
            ]);

        // Verify payments were created
        $this->assertCount(2, Payment::where('user_id', $user->id)
            ->where('status', 'PAID')
            ->get());

        // Verify billing statements were marked as paid
        $this->assertDatabaseHas('billing_statements', [
            'id' => $gymStatement->id,
            'status' => 'PAID',
        ]);

        $this->assertDatabaseHas('billing_statements', [
            'id' => $badmintonStatement->id,
            'status' => 'PAID',
        ]);

        // Verify new subscriptions were created
        $this->assertGreaterThan(2, MembershipSubscription::where('user_id', $user->id)->count());
    }

    public function test_bulk_payment_validates_subscription_ownership(): void
    {
        $user1 = User::factory()->create();
        Member::create(['user_id' => $user1->id]);
        Sanctum::actingAs($user1);

        $user2 = User::factory()->create();
        Member::create(['user_id' => $user2->id]);

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
        $user2Subscription = MembershipSubscription::create([
            'user_id' => $user2->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(3),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        $response = $this->postJson('/api/payments/bulk-pay', [
            'subscription_ids' => [$user2Subscription->id],
            'payment_method' => 'ONLINE_CARD',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Some subscriptions are invalid or not eligible for renewal.',
            ]);
    }

    public function test_bulk_payment_requires_billing_statement(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

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

        // No billing statement created

        $response = $this->postJson('/api/payments/bulk-pay', [
            'subscription_ids' => [$subscription->id],
            'payment_method' => 'ONLINE_CARD',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Billing statement not found for one or more subscriptions.',
            ]);
    }
}
