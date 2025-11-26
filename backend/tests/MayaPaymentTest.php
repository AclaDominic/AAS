<?php

/**
 * Maya Payment Integration Test Script
 * 
 * This script tests the Maya payment integration using sandbox credentials.
 * Run with: php tests/MayaPaymentTest.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Services\MayaPaymentService;
use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "========================================\n";
echo "Maya Payment Integration Test\n";
echo "========================================\n\n";

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
    $mayaService = new MayaPaymentService();
    
    echo "1. Testing Maya Service Initialization...\n";
    echo "   Sandbox Mode: " . (config('services.maya.sandbox') ? 'YES' : 'NO') . "\n";
    echo "   Public Key: " . (config('services.maya.public_key') ? 'SET' : 'NOT SET') . "\n";
    echo "   Secret Key: " . (config('services.maya.secret_key') ? 'SET' : 'NOT SET') . "\n\n";
    
    if (!config('services.maya.public_key') || !config('services.maya.secret_key')) {
        echo "ERROR: Maya credentials not configured in .env file!\n";
        echo "Please add the following to your .env file:\n";
        echo "MAYA_SANDBOX=true\n";
        echo "MAYA_PUBLIC_KEY=pk-eo4sL393CWU5KmveJUaW8V730TTei2zY8zE4dHJDxkF\n";
        echo "MAYA_SECRET_KEY=sk-KfmfLJXFdV5t1inYN8lIOwSrueC1G27SCAklBqYCdrU\n";
        exit(1);
    }
    
    echo "2. Testing Checkout Creation...\n";
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
    ]);
    
    if ($checkoutData['success']) {
        echo "   ✓ Checkout created successfully!\n";
        echo "   Checkout ID: " . $checkoutData['checkout_id'] . "\n";
        echo "   Redirect URL: " . $checkoutData['redirect_url'] . "\n\n";
        
        $checkoutId = $checkoutData['checkout_id'];
        
        echo "3. Testing Payment Verification...\n";
        echo "   Note: Payment verification will show PENDING status until payment is completed.\n";
        echo "   To complete the test:\n";
        echo "   1. Open the redirect URL in your browser\n";
        echo "   2. Complete the payment using one of the test cards below\n";
        echo "   3. Run this script again to verify the payment status\n\n";
        
        $verification = $mayaService->verifyPayment($checkoutId);
        
        if ($verification['success']) {
            echo "   ✓ Payment verification successful!\n";
            echo "   Payment Status: " . $verification['status'] . "\n";
            if ($verification['payment_id']) {
                echo "   Payment ID: " . $verification['payment_id'] . "\n";
            }
        } else {
            echo "   ✗ Payment verification failed: " . ($verification['error'] ?? 'Unknown error') . "\n";
        }
        
        echo "\n";
        echo "4. Test Cards Available:\n";
        echo "   ========================================\n";
        foreach ($testCards as $key => $card) {
            echo "   " . $card['name'] . "\n";
            echo "   Number: " . $card['number'] . "\n";
            echo "   Expiry: " . $card['expiry_month'] . "/" . $card['expiry_year'] . "\n";
            echo "   CVV: " . $card['cvv'] . "\n";
            if (isset($card['password'])) {
                echo "   3D Secure Password: " . $card['password'] . "\n";
            } else {
                echo "   3D Secure: Not enabled\n";
            }
            echo "   ----------------------------------------\n";
        }
        
        echo "\n";
        echo "5. Test User Credentials (for Maya Wallet):\n";
        echo "   Username: " . $testCredentials['username'] . "\n";
        echo "   Password: " . $testCredentials['password'] . "\n";
        echo "   OTP: " . $testCredentials['otp'] . "\n";
        echo "   Result: Successful Transaction\n\n";
        
        echo "========================================\n";
        echo "Test Summary\n";
        echo "========================================\n";
        echo "Checkout ID: " . $checkoutId . "\n";
        echo "Status: " . ($checkoutData['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        echo "\nTo test payment completion:\n";
        echo "1. Visit: " . $checkoutData['redirect_url'] . "\n";
        echo "2. Use one of the test cards above\n";
        echo "3. Complete the payment\n";
        echo "4. Run verification again with checkout ID: " . $checkoutId . "\n";
        
    } else {
        echo "   ✗ Checkout creation failed!\n";
        echo "   Error: " . ($checkoutData['error'] ?? 'Unknown error') . "\n";
        echo "\n";
        echo "Troubleshooting:\n";
        echo "- Check that MAYA_PUBLIC_KEY and MAYA_SECRET_KEY are set in .env\n";
        echo "- Verify the keys are from Sandbox Party 2\n";
        echo "- Check your internet connection\n";
    }
    
} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";



