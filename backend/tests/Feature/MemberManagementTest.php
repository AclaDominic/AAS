<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_members(): void
    {
        // Create admin user (non-member)
        $admin = User::factory()->create();
        // Admin is a user without a member record
        Sanctum::actingAs($admin);

        // Create members
        $member1 = User::factory()->create();
        Member::create(['user_id' => $member1->id]);
        
        $member2 = User::factory()->create();
        Member::create(['user_id' => $member2->id]);

        $response = $this->getJson('/api/admin/members');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'membership_status',
                        'active_subscriptions_count',
                        'total_spent',
                    ],
                ],
            ]);
    }

    public function test_admin_can_search_members(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $member = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Member::create(['user_id' => $member->id]);

        $response = $this->getJson('/api/admin/members?search=John');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('John Doe', $response->json('data.0.name'));
    }

    public function test_admin_can_filter_members_by_status(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $member = User::factory()->create();
        Member::create(['user_id' => $member->id]);
        
        // Create active subscription
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
        MembershipSubscription::create([
            'user_id' => $member->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'is_recurring' => false,
        ]);

        $response = $this->getJson('/api/admin/members?status=active');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_admin_can_get_member_details(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $member = User::factory()->create();
        Member::create(['user_id' => $member->id]);

        $response = $this->getJson("/api/admin/members/{$member->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'membership_status',
                'active_subscriptions',
                'payments',
                'total_spent',
            ]);
    }

    public function test_admin_can_get_member_statistics(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $member = User::factory()->create();
        Member::create(['user_id' => $member->id]);

        $response = $this->getJson('/api/admin/members/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_members',
                'active_members',
                'expired_members',
                'inactive_members',
                'total_revenue',
            ]);
    }

    public function test_user_model_calculates_total_spent_correctly(): void
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
        
        Payment::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'payment_method' => 'CASH',
            'amount' => 1000,
            'status' => 'PAID',
            'payment_date' => now(),
        ]);

        Payment::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'payment_method' => 'CASH',
            'amount' => 500,
            'status' => 'PAID',
            'payment_date' => now(),
        ]);

        $this->assertEquals(1500, $user->getTotalSpent());
    }

    public function test_user_model_calculates_membership_status(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);

        // No subscriptions - should be inactive
        $this->assertEquals('inactive', $user->getMembershipStatus());

        // Add active subscription
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
        MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'price_paid' => 1000,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'is_recurring' => false,
        ]);

        $user->refresh();
        $this->assertEquals('active', $user->getMembershipStatus());
    }
}
