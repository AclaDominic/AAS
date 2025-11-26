<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MayaPaymentService
{
    protected string $publicKey;
    protected string $secretKey;
    protected string $baseUrl;
    protected bool $isSandbox;

    public function __construct()
    {
        $this->isSandbox = config('services.maya.sandbox', true);
        $this->baseUrl = $this->isSandbox 
            ? 'https://pg-sandbox.paymaya.com' 
            : 'https://pg.paymaya.com';
        $this->publicKey = config('services.maya.public_key', '');
        $this->secretKey = config('services.maya.secret_key', '');
    }

    /**
     * Create a checkout session for payment
     * 
     * @param array $paymentData Payment data including:
     *   - amount: Payment amount
     *   - first_name: Buyer's first name
     *   - last_name: Buyer's last name (optional)
     *   - email: Buyer's email
     *   - phone: Buyer's phone (optional)
     *   - item_name: Name of the item/service
     *   - success_url: URL to redirect on success
     *   - failure_url: URL to redirect on failure
     *   - cancel_url: URL to redirect on cancel
     *   - request_reference_number: Reference number (optional)
     *   - payment_method_preference: 'MAYA_WALLET' or 'CARD' (optional)
     * 
     * When payment_method_preference is specified, the checkout will be configured
     * to prioritize that payment method. Users can still switch methods if needed.
     */
    public function createCheckout(array $paymentData): array
    {
        try {
            Log::info('MayaPaymentService: Creating checkout', [
                'amount' => $paymentData['amount'],
                'item_name' => $paymentData['item_name'],
            ]);

            // Prepare buyer data with optional address fields
            $buyerData = [
                'firstName' => $paymentData['first_name'],
                'lastName' => $paymentData['last_name'] ?? '',
                'contact' => [
                    'phone' => $paymentData['phone'] ?? '',
                    'email' => $paymentData['email'],
                ],
            ];

            // Add billing address if provided
            if (isset($paymentData['billing_address'])) {
                $buyerData['billingAddress'] = $paymentData['billing_address'];
            }

            // Add shipping address if provided
            if (isset($paymentData['shipping_address'])) {
                $buyerData['shippingAddress'] = $paymentData['shipping_address'];
            }

            // Create checkout request
            $requestData = [
                'totalAmount' => [
                    'value' => number_format($paymentData['amount'], 2, '.', ''),
                    'currency' => 'PHP',
                ],
                'buyer' => $buyerData,
                'items' => [
                    [
                        'name' => $paymentData['item_name'],
                        'quantity' => 1,
                        'totalAmount' => [
                            'value' => number_format($paymentData['amount'], 2, '.', ''),
                            'currency' => 'PHP',
                        ],
                    ],
                ],
                'redirectUrl' => [
                    'success' => $this->addPaymentMethodToUrl(
                        $paymentData['success_url'], 
                        $paymentData['payment_method_preference'] ?? null
                    ),
                    'failure' => $this->addPaymentMethodToUrl(
                        $paymentData['failure_url'], 
                        $paymentData['payment_method_preference'] ?? null
                    ),
                    'cancel' => $this->addPaymentMethodToUrl(
                        $paymentData['cancel_url'], 
                        $paymentData['payment_method_preference'] ?? null
                    ),
                ],
            ];

            // Add request reference number (required by Maya API)
            // Generate a unique reference number if not provided
            $requestData['requestReferenceNumber'] = $paymentData['request_reference_number'] 
                ?? 'REF-' . time() . '-' . \Illuminate\Support\Str::random(8);

            // For Maya Wallet, we'll redirect directly to wallet login page
            // For Card, we'll use the standard checkout page
            // Note: We don't set paymentMethod in request body as we'll override the redirect URL
            if (isset($paymentData['payment_method_preference'])) {
                Log::info('MayaPaymentService: Payment method preference set', [
                    'preference' => $paymentData['payment_method_preference'],
                ]);
            }

            Log::info('MayaPaymentService: Request data', ['request' => $requestData]);

            $response = Http::withBasicAuth($this->publicKey, $this->secretKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/checkout/v1/checkouts", $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $checkoutId = $data['checkoutId'] ?? null;
                
                Log::info('MayaPaymentService: Checkout created successfully', [
                    'checkout_id' => $checkoutId,
                ]);

                // Use Maya's provided redirectUrl from the API response
                // Maya's redirectUrl is the correct, valid URL that links to the checkout session
                // We should always use this URL instead of manually constructing it
                $redirectUrl = $data['redirectUrl'] ?? null;
                
                if (!$redirectUrl && $checkoutId) {
                    // Fallback: If Maya didn't provide a redirectUrl, log an error
                    // but try to construct it (shouldn't happen in normal operation)
                    Log::warning('MayaPaymentService: redirectUrl not provided in response, constructing fallback', [
                        'checkout_id' => $checkoutId,
                    ]);
                    
                    $paymentBaseUrl = $this->isSandbox 
                        ? 'https://payments-web-sandbox.paymaya.com'
                        : 'https://payments.maya.ph';
                    $redirectUrl = $paymentBaseUrl . '/checkout?id=' . $checkoutId;
                }
                
                Log::info('MayaPaymentService: Using Maya redirectUrl for card payment', [
                    'checkout_id' => $checkoutId,
                    'redirect_url' => $redirectUrl,
                    'environment' => $this->isSandbox ? 'sandbox' : 'production',
                ]);

                return [
                    'success' => true,
                    'checkout_id' => $checkoutId,
                    'redirect_url' => $redirectUrl,
                ];
            }

            $errorResponse = $response->json();
            Log::error('MayaPaymentService: Checkout creation failed', [
                'response' => $errorResponse,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $errorMessage = 'Failed to create checkout';
            if (isset($errorResponse['message'])) {
                $errorMessage = $errorResponse['message'];
            } elseif (isset($errorResponse['error'])) {
                $errorMessage = is_array($errorResponse['error']) 
                    ? json_encode($errorResponse['error']) 
                    : $errorResponse['error'];
            } elseif (isset($errorResponse['errors'])) {
                $errorMessage = json_encode($errorResponse['errors']);
            }

            return [
                'success' => false,
                'error' => $errorMessage,
                'details' => $errorResponse,
            ];
        } catch (\Exception $e) {
            Log::error('MayaPaymentService: Exception creating checkout', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while creating checkout: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $checkoutId): array
    {
        try {
            Log::info('MayaPaymentService: Verifying payment', [
                'checkout_id' => $checkoutId,
            ]);

            // Verification endpoint uses only secret key
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
            ])
                ->get("{$this->baseUrl}/checkout/v1/checkouts/{$checkoutId}");

            if ($response->successful()) {
                $data = $response->json();
                Log::info('MayaPaymentService: Payment verification successful', [
                    'checkout_id' => $checkoutId,
                    'status' => $data['paymentStatus'] ?? 'UNKNOWN',
                ]);

                return [
                    'success' => true,
                    'status' => $data['paymentStatus'] ?? 'UNKNOWN',
                    'payment_id' => $data['paymentId'] ?? null,
                    'payment_token' => $data['paymentToken'] ?? null,
                    'data' => $data,
                ];
            }

            Log::error('MayaPaymentService: Payment verification failed', [
                'checkout_id' => $checkoutId,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to verify payment'),
            ];
        } catch (\Exception $e) {
            Log::error('MayaPaymentService: Exception verifying payment', [
                'checkout_id' => $checkoutId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while verifying payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get test card numbers for sandbox
     */
    public function getTestCards(): array
    {
        return [
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
    }

    /**
     * Get test user credentials for Maya Wallet
     */
    public function getTestUserCredentials(): array
    {
        return [
            'username' => '+639900100900',
            'password' => 'Password@1',
            'otp' => '123456',
            'result' => 'Successful Transaction',
        ];
    }

    /**
     * Add payment method preference to URL as query parameter
     * This helps track which payment method the user intended to use
     */
    private function addPaymentMethodToUrl(string $url, ?string $paymentMethodPreference): string
    {
        if (!$paymentMethodPreference) {
            return $url;
        }

        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . 'payment_method=' . urlencode($paymentMethodPreference);
    }
}

