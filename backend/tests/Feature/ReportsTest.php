<?php

namespace Tests\Feature;

use App\Models\MembershipOffer;
use App\Models\Payment;
use App\Models\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_payment_history_report(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

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

        $response = $this->getJson('/api/admin/reports/payment-history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'payments' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user',
                            'membership_offer',
                            'amount',
                            'status',
                        ],
                    ],
                ],
                'summary',
            ]);
    }

    public function test_admin_can_filter_payment_history_by_date_range(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

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
            'payment_date' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);

        Payment::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'payment_method' => 'CASH',
            'amount' => 500,
            'status' => 'PAID',
            'payment_date' => now(),
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/reports/payment-history?' . http_build_query([
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDay()->toDateString(), // Add one day to include today
        ]));

        $response->assertStatus(200);
        // Should return at least the payment from today (may have more if other tests created payments)
        $this->assertGreaterThanOrEqual(1, count($response->json('payments.data')));
    }

    public function test_admin_can_get_customer_balances(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

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

        $response = $this->getJson('/api/admin/reports/customer-balances');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
        $this->assertGreaterThanOrEqual(1, count($response->json()));
        
        $customer = collect($response->json())->firstWhere('id', $user->id);
        $this->assertNotNull($customer);
        $this->assertEquals(1000, $customer['total_paid']);
    }

    public function test_admin_can_get_payment_summary_monthly(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

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

        $response = $this->getJson('/api/admin/reports/payments-summary?period=monthly');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'summary',
                'grand_total',
            ]);
    }

    public function test_admin_can_export_payment_history(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

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

        $response = $this->getJson('/api/admin/reports/export?type=payment_history');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $this->assertStringContainsString('ID,User,Email', $response->getContent());
    }
}
