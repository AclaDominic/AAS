<?php

namespace Tests\Feature;

use App\Models\BillingStatement;
use App\Models\Invoice;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Member;
use App\Notifications\PaymentReceiptNotification;
use App\Services\InvoiceService;
use App\Services\PaymentReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_create_billing_statement(): void
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
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        $statement = BillingStatement::create([
            'user_id' => $user->id,
            'membership_subscription_id' => $subscription->id,
            'statement_date' => now(),
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount' => 1000,
            'status' => 'PENDING',
            'due_date' => now()->addDays(5),
        ]);

        $this->assertDatabaseHas('billing_statements', [
            'id' => $statement->id,
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 'PENDING',
        ]);
    }

    public function test_can_generate_invoice_for_billing_statement(): void
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
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        $statement = BillingStatement::create([
            'user_id' => $user->id,
            'membership_subscription_id' => $subscription->id,
            'statement_date' => now(),
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount' => 1000,
            'status' => 'PENDING',
            'due_date' => now()->addDays(5),
        ]);

        $invoice = Invoice::create([
            'billing_statement_id' => $statement->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now(),
            'amount' => 1000,
            'status' => 'SENT',
        ]);

        $this->assertDatabaseHas('invoices', [
            'billing_statement_id' => $statement->id,
            'amount' => 1000,
        ]);
        $this->assertNotNull($invoice->invoice_number);
    }

    public function test_can_generate_receipt_for_payment(): void
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
        $payment = Payment::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'payment_method' => 'CASH',
            'amount' => 1000,
            'status' => 'PAID',
            'payment_date' => now(),
        ]);

        $receipt = Receipt::create([
            'payment_id' => $payment->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => now(),
            'amount' => 1000,
        ]);

        $this->assertDatabaseHas('receipts', [
            'payment_id' => $payment->id,
            'amount' => 1000,
        ]);
        $this->assertNotNull($receipt->receipt_number);
    }

    public function test_member_can_get_pending_renewals(): void
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
            'end_date' => now()->addDays(3), // Expires in 3 days
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        $response = $this->getJson('/api/memberships/pending-renewals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'subscriptions',
                'total_amount',
                'count',
            ]);
        
        $this->assertGreaterThanOrEqual(1, count($response->json('subscriptions')));
    }

    public function test_subscription_is_due_for_renewal(): void
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

        $this->assertTrue($subscription->isDueForRenewal());
        $this->assertLessThanOrEqual(5, $subscription->getDaysUntilExpiration());
    }

    public function test_user_calculates_total_owed_from_billing_statements(): void
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
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        BillingStatement::create([
            'user_id' => $user->id,
            'membership_subscription_id' => $subscription->id,
            'statement_date' => now(),
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount' => 1000,
            'status' => 'PENDING',
            'due_date' => now()->addDays(5),
        ]);

        BillingStatement::create([
            'user_id' => $user->id,
            'membership_subscription_id' => $subscription->id,
            'statement_date' => now(),
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount' => 500,
            'status' => 'PENDING',
            'due_date' => now()->addDays(5),
        ]);

        $this->assertEquals(1500, $user->getTotalOwed());
    }

    public function test_payment_receipt_service_dispatches_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);

        $offer = MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Receipt Plan',
            'description' => 'Test plan',
            'price' => 500,
            'billing_type' => 'NON_RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'payment_method' => 'CASH',
            'amount' => 500,
            'status' => 'PAID',
            'payment_date' => now(),
        ]);

        $receiptPath = 'receipts/test-receipt.pdf';
        Storage::disk('local')->put($receiptPath, 'PDF DATA');

        Receipt::create([
            'payment_id' => $payment->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => now(),
            'amount' => $payment->amount,
            'pdf_path' => $receiptPath,
        ]);

        $invoiceService = Mockery::mock(InvoiceService::class);
        $invoiceService->shouldReceive('getReceiptPdfPath')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->is($payment)))
            ->andReturn($receiptPath);

        $service = new PaymentReceiptService($invoiceService);
        $service->deliver($payment);

        Notification::assertSentTo($user, PaymentReceiptNotification::class);
    }

    public function test_member_can_download_receipt_pdf(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $offer = MembershipOffer::create([
            'category' => 'GYM',
            'name' => 'Downloadable Plan',
            'description' => 'Test plan',
            'price' => 800,
            'billing_type' => 'NON_RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'membership_offer_id' => $offer->id,
            'payment_method' => 'CASH',
            'amount' => 800,
            'status' => 'PAID',
            'payment_date' => now(),
        ]);

        $receiptPath = 'receipts/manual-download.pdf';
        Storage::disk('local')->put($receiptPath, 'PDF DATA');

        $receipt = Receipt::create([
            'payment_id' => $payment->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => now(),
            'amount' => $payment->amount,
            'pdf_path' => $receiptPath,
        ]);

        $response = $this->get("/api/payments/{$payment->id}/receipt");

        $response->assertStatus(200);
        $this->assertStringContainsString(
            "receipt-{$receipt->receipt_number}",
            $response->headers->get('content-disposition')
        );
    }
}
