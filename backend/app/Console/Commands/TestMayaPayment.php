<?php

namespace App\Console\Commands;

use App\Services\MayaPaymentService;
use Illuminate\Console\Command;

class TestMayaPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maya:test-payment {--checkout-id= : Verify an existing checkout ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Maya payment integration with sandbox credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('Maya Payment Integration Test');
        $this->info('========================================');
        $this->newLine();

        // Check if verifying existing checkout
        $checkoutId = $this->option('checkout-id');
        if ($checkoutId) {
            return $this->verifyPayment($checkoutId);
        }

        // Test credentials
        $testCredentials = [
            'username' => '+639900100900',
            'password' => 'Password@1',
            'otp' => '123456',
        ];

        $testCards = [
            'mastercard_1' => [
                'number' => '5123456789012346',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '111',
                'name' => 'Mastercard (No 3D Secure)',
            ],
            'mastercard_2' => [
                'number' => '5453010000064154',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '111',
                'password' => 'secbarry1',
                'name' => 'Mastercard (3D Secure)',
            ],
            'visa_1' => [
                'number' => '4123450131001381',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
                'password' => 'mctest1',
                'name' => 'Visa (3D Secure)',
            ],
            'visa_2' => [
                'number' => '4123450131001522',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
                'password' => 'mctest1',
                'name' => 'Visa (3D Secure)',
            ],
            'visa_3' => [
                'number' => '4123450131004443',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
                'password' => 'mctest1',
                'name' => 'Visa (3D Secure)',
            ],
            'visa_4' => [
                'number' => '4123450131000508',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '111',
                'name' => 'Visa (No 3D Secure)',
            ],
        ];

        try {
            $this->info('1. Testing Maya Service Initialization...');
            $sandbox = config('services.maya.sandbox', true);
            $publicKey = config('services.maya.public_key');
            $secretKey = config('services.maya.secret_key');

            $this->line('   Sandbox Mode: ' . ($sandbox ? 'YES' : 'NO'));
            $this->line('   Public Key: ' . ($publicKey ? 'SET' : 'NOT SET'));
            $this->line('   Secret Key: ' . ($secretKey ? 'SET' : 'NOT SET'));
            $this->newLine();

            if (!$publicKey || !$secretKey) {
                $this->error('ERROR: Maya credentials not configured in .env file!');
                $this->newLine();
                $this->line('Please add the following to your .env file:');
                $this->line('MAYA_SANDBOX=true');
                $this->line('MAYA_PUBLIC_KEY=pk-eo4sL393CWU5KmveJUaW8V730TTei2zY8zE4dHJDxkF');
                $this->line('MAYA_SECRET_KEY=sk-KfmfLJXFdV5t1inYN8lIOwSrueC1G27SCAklBqYCdrU');
                return 1;
            }

            $this->info('2. Testing Checkout Creation...');
            $mayaService = new MayaPaymentService();
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

            $checkoutData = $mayaService->createCheckout([
                'amount' => 49.99,
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone' => '+639900100900',
                'item_name' => 'Monthly Gym Membership',
                'success_url' => $frontendUrl . '/payment/success?payment_id=test123',
                'failure_url' => $frontendUrl . '/payment/failure?payment_id=test123',
                'cancel_url' => $frontendUrl . '/payment/cancel?payment_id=test123',
                'request_reference_number' => 'TEST-' . time() . '-' . \Illuminate\Support\Str::random(8),
            ]);

            if ($checkoutData['success']) {
                $this->info('   ✓ Checkout created successfully!');
                $this->line('   Checkout ID: ' . $checkoutData['checkout_id']);
                $this->line('   Redirect URL: ' . $checkoutData['redirect_url']);
                $this->newLine();

                $checkoutId = $checkoutData['checkout_id'];

                $this->info('3. Testing Payment Verification...');
                $this->line('   Note: Payment verification will show PENDING status until payment is completed.');
                $this->line('   To complete the test:');
                $this->line('   1. Open the redirect URL in your browser');
                $this->line('   2. Complete the payment using one of the test cards below');
                $this->line('   3. Run: php artisan maya:test-payment --checkout-id=' . $checkoutId);
                $this->newLine();

                $verification = $mayaService->verifyPayment($checkoutId);

                if ($verification['success']) {
                    $this->info('   ✓ Payment verification successful!');
                    $this->line('   Payment Status: ' . $verification['status']);
                    if ($verification['payment_id']) {
                        $this->line('   Payment ID: ' . $verification['payment_id']);
                    }
                } else {
                    $this->error('   ✗ Payment verification failed: ' . ($verification['error'] ?? 'Unknown error'));
                }

                $this->newLine();
                $this->info('4. Test Cards Available:');
                $this->line('   ========================================');
                foreach ($testCards as $key => $card) {
                    $this->line('   ' . $card['name']);
                    $this->line('   Number: ' . $card['number']);
                    $this->line('   Expiry: ' . $card['expiry_month'] . '/' . $card['expiry_year']);
                    $this->line('   CVV: ' . $card['cvv']);
                    if (isset($card['password'])) {
                        $this->line('   3D Secure Password: ' . $card['password']);
                    } else {
                        $this->line('   3D Secure: Not enabled');
                    }
                    $this->line('   ----------------------------------------');
                }

                $this->newLine();
                $this->info('5. Test User Credentials (for Maya Wallet):');
                $this->line('   Username: ' . $testCredentials['username']);
                $this->line('   Password: ' . $testCredentials['password']);
                $this->line('   OTP: ' . $testCredentials['otp']);
                $this->line('   Result: Successful Transaction');
                $this->newLine();

                $this->info('========================================');
                $this->info('Test Summary');
                $this->info('========================================');
                $this->line('Checkout ID: ' . $checkoutId);
                $this->line('Status: SUCCESS');
                $this->newLine();
                $this->line('To test payment completion:');
                $this->line('1. Visit: ' . $checkoutData['redirect_url']);
                $this->line('2. Use one of the test cards above');
                $this->line('3. Complete the payment');
                $this->line('4. Run: php artisan maya:test-payment --checkout-id=' . $checkoutId);

            } else {
                $this->error('   ✗ Checkout creation failed!');
                $this->error('   Error: ' . ($checkoutData['error'] ?? 'Unknown error'));
                $this->newLine();
                $this->line('Troubleshooting:');
                $this->line('- Check that MAYA_PUBLIC_KEY and MAYA_SECRET_KEY are set in .env');
                $this->line('- Verify the keys are from Sandbox Party 2');
                $this->line('- Check your internet connection');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('ERROR: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Verify an existing payment
     */
    private function verifyPayment(string $checkoutId)
    {
        $this->info('Verifying Payment Status...');
        $this->line('Checkout ID: ' . $checkoutId);
        $this->newLine();

        try {
            $mayaService = new MayaPaymentService();
            $verification = $mayaService->verifyPayment($checkoutId);

            if ($verification['success']) {
                $this->info('✓ Payment verification successful!');
                $this->newLine();
                $this->line('Payment Status: ' . $verification['status']);
                
                if ($verification['payment_id']) {
                    $this->line('Payment ID: ' . $verification['payment_id']);
                }
                
                if ($verification['payment_token']) {
                    $this->line('Payment Token: ' . $verification['payment_token']);
                }

                $this->newLine();
                
                if ($verification['status'] === 'PAYMENT_SUCCESS') {
                    $this->info('✓ Payment completed successfully!');
                } elseif ($verification['status'] === 'PAYMENT_FAILED') {
                    $this->error('✗ Payment failed');
                } else {
                    $this->warn('Payment status: ' . $verification['status']);
                }

                // Show metadata if available
                if (isset($verification['data']) && is_array($verification['data'])) {
                    $this->newLine();
                    $this->line('Payment Details:');
                    $this->line(json_encode($verification['data'], JSON_PRETTY_PRINT));
                }

            } else {
                $this->error('✗ Payment verification failed!');
                $this->error('Error: ' . ($verification['error'] ?? 'Unknown error'));
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('ERROR: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
