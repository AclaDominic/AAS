<?php

namespace Tests\Feature;

use App\Models\FirstTimeDiscount;
use App\Models\Member;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Promo;
use App\Models\User;
use App\Models\UserPromoUsage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MembershipPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected $memberUser;
    protected $gymOffer1;
    protected $gymOffer2;
    protected $badmintonOffer;
    protected $promo;
    protected $firstTimeDiscount;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a member user
        $this->memberUser = User::create([
            'name' => 'Test Member',
            'email' => 'member@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        Member::create([
            'user_id' => $this->memberUser->id,
        ]);

        // Create membership offers
        $this->gymOffer1 = MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Monthly Gym Membership',
            'description' => 'Test gym offer',
            'price' => 49.99,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        $this->gymOffer2 = MembershipOffer::create([
            'category' => 'GYM',
            'name' => '3-Month Gym Package',
            'description' => 'Test gym offer 2',
            'price' => 129.99,
            'billing_type' => 'NON_RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 3,
            'is_active' => true,
        ]);

        $this->badmintonOffer = MembershipOffer::create([
            'category' => 'BADMINTON_COURT',
            'name' => 'Annual Badminton Court Membership',
            'description' => 'Test badminton offer',
            'price' => 299.99,
            'billing_type' => 'RECURRING',
            'duration_type' => 'YEAR',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        // Create a promo
        $this->promo = Promo::create([
            'name' => 'Test Promo',
            'description' => 'Test promo description',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 20.00,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(30),
            'is_active' => true,
            'applicable_to_category' => 'ALL',
        ]);

        // Create a first-time discount
        $this->firstTimeDiscount = FirstTimeDiscount::create([
            'name' => 'Welcome Discount',
            'description' => 'First-time member discount',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 15.00,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(60),
            'is_active' => true,
            'applicable_to_category' => 'ALL',
        ]);
    }

    public function test_member_can_purchase_membership_with_promo(): void
    {
        Sanctum::actingAs($this->memberUser);

        $response = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
            'promo_id' => $this->promo->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'subscription' => [
                    'id',
                    'user_id',
                    'membership_offer_id',
                    'promo_id',
                    'price_paid',
                    'status',
                ],
            ]);

        // Verify subscription was created
        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $this->memberUser->id,
            'membership_offer_id' => $this->gymOffer1->id,
            'promo_id' => $this->promo->id,
            'status' => 'ACTIVE',
        ]);

        // Verify promo usage was recorded
        $this->assertDatabaseHas('user_promo_usage', [
            'user_id' => $this->memberUser->id,
            'promo_id' => $this->promo->id,
        ]);

        // Verify price was discounted (20% off $49.99 = $39.99)
        $subscription = MembershipSubscription::where('user_id', $this->memberUser->id)->first();
        $this->assertEquals(39.99, round($subscription->price_paid, 2));
    }

    public function test_member_cannot_have_two_active_memberships_of_same_category(): void
    {
        Sanctum::actingAs($this->memberUser);

        // Purchase first GYM membership
        $response1 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
        ]);

        $response1->assertStatus(201);

        // Try to purchase second GYM membership (should fail)
        $response2 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer2->id,
        ]);

        $response2->assertStatus(400)
            ->assertJson([
                'message' => 'You already have an active GYM membership. You cannot have multiple active memberships of the same category.',
            ]);

        // Verify only one subscription exists
        $this->assertEquals(1, MembershipSubscription::where('user_id', $this->memberUser->id)
            ->where('status', 'ACTIVE')
            ->count());
    }

    public function test_member_can_have_one_membership_from_each_category(): void
    {
        Sanctum::actingAs($this->memberUser);

        // Purchase GYM membership
        $response1 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
        ]);

        $response1->assertStatus(201);

        // Purchase BADMINTON_COURT membership (should succeed)
        $response2 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->badmintonOffer->id,
        ]);

        $response2->assertStatus(201);

        // Verify both subscriptions exist
        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $this->memberUser->id,
            'membership_offer_id' => $this->gymOffer1->id,
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $this->memberUser->id,
            'membership_offer_id' => $this->badmintonOffer->id,
            'status' => 'ACTIVE',
        ]);

        // Verify we have 2 active subscriptions
        $this->assertEquals(2, MembershipSubscription::where('user_id', $this->memberUser->id)
            ->where('status', 'ACTIVE')
            ->count());
    }

    public function test_member_can_use_promo_on_different_category_memberships(): void
    {
        Sanctum::actingAs($this->memberUser);

        // Purchase GYM membership with promo
        $response1 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
            'promo_id' => $this->promo->id,
        ]);

        $response1->assertStatus(201);

        // Purchase BADMINTON_COURT membership with same promo (should succeed)
        $response2 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->badmintonOffer->id,
            'promo_id' => $this->promo->id,
        ]);

        $response2->assertStatus(201);

        // Verify both subscriptions used the promo
        $this->assertEquals(2, MembershipSubscription::where('user_id', $this->memberUser->id)
            ->where('promo_id', $this->promo->id)
            ->count());

        // Verify promo usage was recorded twice
        $this->assertEquals(2, UserPromoUsage::where('user_id', $this->memberUser->id)
            ->where('promo_id', $this->promo->id)
            ->count());
    }

    public function test_new_member_can_use_first_time_discount(): void
    {
        Sanctum::actingAs($this->memberUser);

        // New member should be able to use first-time discount
        $response = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'subscription' => [
                    'id',
                    'user_id',
                    'membership_offer_id',
                    'first_time_discount_id',
                    'price_paid',
                    'status',
                ],
            ]);

        // Verify subscription was created with discount
        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $this->memberUser->id,
            'membership_offer_id' => $this->gymOffer1->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
            'status' => 'ACTIVE',
        ]);

        // Verify discount usage was recorded
        $this->assertDatabaseHas('user_discount_usage', [
            'user_id' => $this->memberUser->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);

        // Verify price was discounted (15% off $49.99 = $42.49)
        $subscription = MembershipSubscription::where('user_id', $this->memberUser->id)->first();
        $this->assertEquals(42.49, round($subscription->price_paid, 2));
    }

    public function test_member_cannot_take_first_time_discount_if_not_new(): void
    {
        Sanctum::actingAs($this->memberUser);

        // First, purchase a membership without discount (making them not "new")
        $response1 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
        ]);

        $response1->assertStatus(201);

        // Now try to purchase another membership with first-time discount (should fail)
        $response2 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->badmintonOffer->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);

        $response2->assertStatus(400)
            ->assertJson([
                'message' => 'You are not eligible for first-time discounts. You have already used a promo or purchased a membership.',
            ]);

        // Verify first-time discount was not used
        $this->assertDatabaseMissing('user_discount_usage', [
            'user_id' => $this->memberUser->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);
    }

    public function test_member_cannot_take_first_time_discount_if_has_used_any_promo(): void
    {
        Sanctum::actingAs($this->memberUser);

        // First, purchase a membership with a promo (making them ineligible for first-time discount)
        $response1 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
            'promo_id' => $this->promo->id,
        ]);

        $response1->assertStatus(201);

        // Now try to purchase another membership with first-time discount (should fail)
        $response2 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->badmintonOffer->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);

        $response2->assertStatus(400)
            ->assertJson([
                'message' => 'You are not eligible for first-time discounts. You have already used a promo or purchased a membership.',
            ]);

        // Verify first-time discount was not used
        $this->assertDatabaseMissing('user_discount_usage', [
            'user_id' => $this->memberUser->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);
    }

    public function test_member_cannot_take_promo_and_first_time_discount_at_same_time(): void
    {
        Sanctum::actingAs($this->memberUser);

        // Try to purchase with both promo and first-time discount (should fail)
        $response = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
            'promo_id' => $this->promo->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'You cannot use both a promo and a first-time discount on the same membership purchase. Please choose one.',
            ]);

        // Verify no subscription was created
        $this->assertDatabaseMissing('membership_subscriptions', [
            'user_id' => $this->memberUser->id,
            'membership_offer_id' => $this->gymOffer1->id,
        ]);

        // Verify neither promo nor discount usage was recorded
        $this->assertDatabaseMissing('user_promo_usage', [
            'user_id' => $this->memberUser->id,
            'promo_id' => $this->promo->id,
        ]);

        $this->assertDatabaseMissing('user_discount_usage', [
            'user_id' => $this->memberUser->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);
    }

    public function test_member_can_use_first_time_discount_on_one_category_and_promo_on_other_category(): void
    {
        Sanctum::actingAs($this->memberUser);

        // Purchase GYM membership with first-time discount
        $response1 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->gymOffer1->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);

        $response1->assertStatus(201);

        // Purchase BADMINTON_COURT membership with promo (should succeed - different category)
        $response2 = $this->postJson('/api/memberships/purchase', [
            'membership_offer_id' => $this->badmintonOffer->id,
            'promo_id' => $this->promo->id,
        ]);

        $response2->assertStatus(201);

        // Verify both subscriptions exist
        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $this->memberUser->id,
            'membership_offer_id' => $this->gymOffer1->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
            'promo_id' => null,
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $this->memberUser->id,
            'membership_offer_id' => $this->badmintonOffer->id,
            'promo_id' => $this->promo->id,
            'first_time_discount_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Verify first-time discount was used on GYM
        $this->assertDatabaseHas('user_discount_usage', [
            'user_id' => $this->memberUser->id,
            'first_time_discount_id' => $this->firstTimeDiscount->id,
        ]);

        // Verify promo was used on BADMINTON_COURT
        $this->assertDatabaseHas('user_promo_usage', [
            'user_id' => $this->memberUser->id,
            'promo_id' => $this->promo->id,
        ]);
    }
}
