<?php

namespace Database\Seeders;

use App\Models\BillingStatement;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing membership offers
        $monthlyGym = MembershipOffer::where('name', 'Monthly Gym Membership')->first();
        $annualBadminton = MembershipOffer::where('name', 'Annual Badminton Court Membership')->first();
        $threeMonthGym = MembershipOffer::where('name', '3-Month Gym Package')->first();

        // Create additional test members
        $members = [];

        // Member 1: Has active recurring gym subscription expiring in 3 days (should show pending renewal)
        $member1 = User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Member::create(['user_id' => $member1->id]);
        $members[] = $member1;

        // Create payment and subscription for member1
        $payment1 = Payment::create([
            'user_id' => $member1->id,
            'membership_offer_id' => $monthlyGym->id,
            'payment_method' => 'ONLINE_CARD',
            'amount' => 49.99,
            'status' => 'PAID',
            'payment_date' => now()->subMonths(1),
        ]);

        $subscription1 = MembershipSubscription::create([
            'user_id' => $member1->id,
            'payment_id' => $payment1->id,
            'membership_offer_id' => $monthlyGym->id,
            'price_paid' => 49.99,
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addDays(3), // Expires in 3 days
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        // Create receipt for payment1
        Receipt::create([
            'payment_id' => $payment1->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $payment1->payment_date,
            'amount' => $payment1->amount,
        ]);

        // Create pending billing statement for subscription1 (due for renewal)
        $billingStatement1 = BillingStatement::create([
            'user_id' => $member1->id,
            'membership_subscription_id' => $subscription1->id,
            'statement_date' => now(),
            'period_start' => $subscription1->end_date,
            'period_end' => $subscription1->end_date->copy()->addMonth(),
            'amount' => $monthlyGym->price,
            'status' => 'PENDING',
            'due_date' => $subscription1->end_date->copy()->addDay(),
        ]);

        Invoice::create([
            'billing_statement_id' => $billingStatement1->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now(),
            'amount' => $billingStatement1->amount,
            'status' => 'SENT',
        ]);

        // Member 2: Has active recurring badminton subscription expiring in 6 days
        $member2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Member::create(['user_id' => $member2->id]);
        $members[] = $member2;

        $payment2 = Payment::create([
            'user_id' => $member2->id,
            'membership_offer_id' => $annualBadminton->id,
            'payment_method' => 'ONLINE_MAYA',
            'amount' => 299.99,
            'status' => 'PAID',
            'payment_date' => now()->subYear(),
        ]);

        $subscription2 = MembershipSubscription::create([
            'user_id' => $member2->id,
            'payment_id' => $payment2->id,
            'membership_offer_id' => $annualBadminton->id,
            'price_paid' => 299.99,
            'start_date' => now()->subYear(),
            'end_date' => now()->addDays(6), // Expires in 6 days
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        Receipt::create([
            'payment_id' => $payment2->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $payment2->payment_date,
            'amount' => $payment2->amount,
        ]);

        // Member 3: Has BOTH gym and badminton subscriptions (for testing bulk pay)
        $member3 = User::create([
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Member::create(['user_id' => $member3->id]);
        $members[] = $member3;

        // Gym subscription for member3
        $payment3a = Payment::create([
            'user_id' => $member3->id,
            'membership_offer_id' => $monthlyGym->id,
            'payment_method' => 'ONLINE_CARD',
            'amount' => 49.99,
            'status' => 'PAID',
            'payment_date' => now()->subMonths(1),
        ]);

        $subscription3a = MembershipSubscription::create([
            'user_id' => $member3->id,
            'payment_id' => $payment3a->id,
            'membership_offer_id' => $monthlyGym->id,
            'price_paid' => 49.99,
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addDays(4), // Expires in 4 days
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        Receipt::create([
            'payment_id' => $payment3a->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $payment3a->payment_date,
            'amount' => $payment3a->amount,
        ]);

        $billingStatement3a = BillingStatement::create([
            'user_id' => $member3->id,
            'membership_subscription_id' => $subscription3a->id,
            'statement_date' => now(),
            'period_start' => $subscription3a->end_date,
            'period_end' => $subscription3a->end_date->copy()->addMonth(),
            'amount' => $monthlyGym->price,
            'status' => 'PENDING',
            'due_date' => $subscription3a->end_date->copy()->addDay(),
        ]);

        Invoice::create([
            'billing_statement_id' => $billingStatement3a->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now(),
            'amount' => $billingStatement3a->amount,
            'status' => 'SENT',
        ]);

        // Badminton subscription for member3
        $payment3b = Payment::create([
            'user_id' => $member3->id,
            'membership_offer_id' => $annualBadminton->id,
            'payment_method' => 'ONLINE_MAYA',
            'amount' => 299.99,
            'status' => 'PAID',
            'payment_date' => now()->subYear(),
        ]);

        $subscription3b = MembershipSubscription::create([
            'user_id' => $member3->id,
            'payment_id' => $payment3b->id,
            'membership_offer_id' => $annualBadminton->id,
            'price_paid' => 299.99,
            'start_date' => now()->subYear(),
            'end_date' => now()->addDays(5), // Expires in 5 days
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        Receipt::create([
            'payment_id' => $payment3b->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $payment3b->payment_date,
            'amount' => $payment3b->amount,
        ]);

        $billingStatement3b = BillingStatement::create([
            'user_id' => $member3->id,
            'membership_subscription_id' => $subscription3b->id,
            'statement_date' => now(),
            'period_start' => $subscription3b->end_date,
            'period_end' => $subscription3b->end_date->copy()->addYear(),
            'amount' => $annualBadminton->price,
            'status' => 'PENDING',
            'due_date' => $subscription3b->end_date->copy()->addDay(),
        ]);

        Invoice::create([
            'billing_statement_id' => $billingStatement3b->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now(),
            'amount' => $billingStatement3b->amount,
            'status' => 'SENT',
        ]);

        // Member 4: Has paid billing statement (for testing paid invoices)
        $member4 = User::create([
            'name' => 'Alice Williams',
            'email' => 'alice.williams@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Member::create(['user_id' => $member4->id]);
        $members[] = $member4;

        $payment4a = Payment::create([
            'user_id' => $member4->id,
            'membership_offer_id' => $monthlyGym->id,
            'payment_method' => 'ONLINE_CARD',
            'amount' => 49.99,
            'status' => 'PAID',
            'payment_date' => now()->subMonths(2),
        ]);

        $subscription4 = MembershipSubscription::create([
            'user_id' => $member4->id,
            'payment_id' => $payment4a->id,
            'membership_offer_id' => $monthlyGym->id,
            'price_paid' => 49.99,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        Receipt::create([
            'payment_id' => $payment4a->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $payment4a->payment_date,
            'amount' => $payment4a->amount,
        ]);

        // Create a paid billing statement
        $billingStatement4 = BillingStatement::create([
            'user_id' => $member4->id,
            'membership_subscription_id' => $subscription4->id,
            'statement_date' => now()->subMonth(),
            'period_start' => $subscription4->end_date,
            'period_end' => $subscription4->end_date->copy()->addMonth(),
            'amount' => $monthlyGym->price,
            'status' => 'PAID',
            'due_date' => $subscription4->end_date->copy()->addDay(),
            'payment_id' => $payment4a->id,
        ]);

        Invoice::create([
            'billing_statement_id' => $billingStatement4->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => $billingStatement4->statement_date,
            'amount' => $billingStatement4->amount,
            'status' => 'PAID',
        ]);

        // Member 5: Has inactive/expired subscription
        $member5 = User::create([
            'name' => 'Charlie Brown',
            'email' => 'charlie.brown@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Member::create(['user_id' => $member5->id]);
        $members[] = $member5;

        $payment5 = Payment::create([
            'user_id' => $member5->id,
            'membership_offer_id' => $threeMonthGym->id,
            'payment_method' => 'ONLINE_CARD',
            'amount' => 129.99,
            'status' => 'PAID',
            'payment_date' => now()->subMonths(4),
        ]);

        MembershipSubscription::create([
            'user_id' => $member5->id,
            'payment_id' => $payment5->id,
            'membership_offer_id' => $threeMonthGym->id,
            'price_paid' => 129.99,
            'start_date' => now()->subMonths(4),
            'end_date' => now()->subMonth(), // Expired
            'status' => 'EXPIRED',
            'is_recurring' => false,
        ]);

        Receipt::create([
            'payment_id' => $payment5->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $payment5->payment_date,
            'amount' => $payment5->amount,
        ]);

        // Member 6: Has pending payment
        $member6 = User::create([
            'name' => 'Diana Prince',
            'email' => 'diana.prince@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Member::create(['user_id' => $member6->id]);
        $members[] = $member6;

        Payment::create([
            'user_id' => $member6->id,
            'membership_offer_id' => $monthlyGym->id,
            'payment_method' => 'ONLINE_CARD',
            'amount' => 49.99,
            'status' => 'PENDING',
            'payment_code' => Payment::generatePaymentCode(),
        ]);

        // Member 7: DEDICATED BILLING TEST ACCOUNT - Has multiple billing statements
        $billingTestUser = User::create([
            'name' => 'Billing Test User',
            'email' => 'billing@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Member::create(['user_id' => $billingTestUser->id]);
        $members[] = $billingTestUser;

        // Create active subscription expiring in 3 days (will auto-generate billing statement)
        $paymentBilling1 = Payment::create([
            'user_id' => $billingTestUser->id,
            'membership_offer_id' => $monthlyGym->id,
            'payment_method' => 'ONLINE_CARD',
            'amount' => 49.99,
            'status' => 'PAID',
            'payment_date' => now()->subMonths(1),
        ]);

        $subscriptionBilling1 = MembershipSubscription::create([
            'user_id' => $billingTestUser->id,
            'payment_id' => $paymentBilling1->id,
            'membership_offer_id' => $monthlyGym->id,
            'price_paid' => 49.99,
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addDays(3), // Expires in 3 days - will trigger auto-billing
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        Receipt::create([
            'payment_id' => $paymentBilling1->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $paymentBilling1->payment_date,
            'amount' => $paymentBilling1->amount,
        ]);

        // Create existing billing statement with invoice (for testing download)
        $billingStatementTest1 = BillingStatement::create([
            'user_id' => $billingTestUser->id,
            'membership_subscription_id' => $subscriptionBilling1->id,
            'statement_date' => now()->subDays(2),
            'period_start' => $subscriptionBilling1->end_date,
            'period_end' => $subscriptionBilling1->end_date->copy()->addMonth(),
            'amount' => $monthlyGym->price,
            'status' => 'PENDING',
            'due_date' => $subscriptionBilling1->end_date,
        ]);

        $paymentBilling2 = Payment::create([
            'user_id' => $billingTestUser->id,
            'membership_offer_id' => $monthlyGym->id,
            'payment_method' => 'CASH',
            'amount' => $monthlyGym->price,
            'status' => 'PENDING',
            'payment_code' => Payment::generatePaymentCode(),
        ]);

        $billingStatementTest1->payment_id = $paymentBilling2->id;
        $billingStatementTest1->save();

        Invoice::create([
            'billing_statement_id' => $billingStatementTest1->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => $billingStatementTest1->statement_date,
            'amount' => $billingStatementTest1->amount,
            'status' => 'SENT',
        ]);

        // Create a paid billing statement (for testing paid invoices and receipts)
        $paymentBilling3 = Payment::create([
            'user_id' => $billingTestUser->id,
            'membership_offer_id' => $annualBadminton->id,
            'payment_method' => 'ONLINE_MAYA',
            'amount' => $annualBadminton->price,
            'status' => 'PAID',
            'payment_date' => now()->subDays(5),
        ]);

        $subscriptionBilling2 = MembershipSubscription::create([
            'user_id' => $billingTestUser->id,
            'payment_id' => $paymentBilling3->id,
            'membership_offer_id' => $annualBadminton->id,
            'price_paid' => $annualBadminton->price,
            'start_date' => now()->subYear(),
            'end_date' => now()->subDays(5),
            'status' => 'ACTIVE',
            'is_recurring' => true,
        ]);

        Receipt::create([
            'payment_id' => $paymentBilling3->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'receipt_date' => $paymentBilling3->payment_date,
            'amount' => $paymentBilling3->amount,
        ]);

        $billingStatementTest2 = BillingStatement::create([
            'user_id' => $billingTestUser->id,
            'membership_subscription_id' => $subscriptionBilling2->id,
            'statement_date' => now()->subDays(10),
            'period_start' => $subscriptionBilling2->end_date,
            'period_end' => $subscriptionBilling2->end_date->copy()->addYear(),
            'amount' => $annualBadminton->price,
            'status' => 'PAID',
            'due_date' => $subscriptionBilling2->end_date,
            'payment_id' => $paymentBilling3->id,
        ]);

        Invoice::create([
            'billing_statement_id' => $billingStatementTest2->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => $billingStatementTest2->statement_date,
            'amount' => $billingStatementTest2->amount,
            'status' => 'PAID',
        ]);

        $this->command->info('Test data seeded successfully!');
        $this->command->info('Created ' . count($members) . ' test members with various subscription and payment scenarios.');
        $this->command->info('');
        $this->command->info('=== BILLING TEST ACCOUNT ===');
        $this->command->info('Email: billing@test.com');
        $this->command->info('Password: password');
        $this->command->info('This account has:');
        $this->command->info('  - Active subscription expiring in 3 days (will auto-generate billing)');
        $this->command->info('  - 1 PENDING billing statement with invoice (can download)');
        $this->command->info('  - 1 PAID billing statement with invoice and receipt (can download both)');
        $this->command->info('');
    }
}

