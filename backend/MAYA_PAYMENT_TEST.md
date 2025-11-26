# Maya Payment Integration Test Guide

## Setup

Before testing, ensure your `.env` file contains the following Maya sandbox credentials:

```env
MAYA_SANDBOX=true
MAYA_PUBLIC_KEY=pk-eo4sL393CWU5KmveJUaW8V730TTei2zY8zE4dHJDxkF
MAYA_SECRET_KEY=sk-KfmfLJXFdV5t1inYN8lIOwSrueC1G27SCAklBqYCdrU
FRONTEND_URL=http://localhost:5173
```

## Running the Test

### 1. Create a Test Checkout

Run the test command to create a Maya checkout session:

```bash
php artisan maya:test-payment
```

This will:
- Verify Maya service configuration
- Create a test checkout session
- Display the checkout URL and test credentials
- Show all available test cards

### 2. Complete the Payment

1. Copy the **Redirect URL** from the test output
2. Open it in your browser
3. Use one of the test cards below to complete the payment

### 3. Verify Payment Status

After completing the payment, verify the status using the checkout ID:

```bash
php artisan maya:test-payment --checkout-id=YOUR_CHECKOUT_ID
```

## Test Cards

### Mastercard Cards

**Card 1: No 3D Secure**
- Number: `5123456789012346`
- Expiry: `12/2025`
- CVV: `111`
- 3D Secure: Not enabled

**Card 2: With 3D Secure**
- Number: `5453010000064154`
- Expiry: `12/2025`
- CVV: `111`
- 3D Secure Password: `secbarry1`

### Visa Cards

**Card 1: With 3D Secure**
- Number: `4123450131001381`
- Expiry: `12/2025`
- CVV: `123`
- 3D Secure Password: `mctest1`

**Card 2: With 3D Secure**
- Number: `4123450131001522`
- Expiry: `12/2025`
- CVV: `123`
- 3D Secure Password: `mctest1`

**Card 3: With 3D Secure**
- Number: `4123450131004443`
- Expiry: `12/2025`
- CVV: `123`
- 3D Secure Password: `mctest1`

**Card 4: No 3D Secure**
- Number: `4123450131000508`
- Expiry: `12/2025`
- CVV: `111`
- 3D Secure: Not enabled

## Test User Credentials (Maya Wallet)

For testing Maya Wallet payments:

- **Username:** `+639900100900`
- **Password:** `Password@1`
- **OTP:** `123456`
- **Result:** Successful Transaction

## Test Flow

1. **Create Checkout**
   ```bash
   php artisan maya:test-payment
   ```
   - Note the checkout ID and redirect URL

2. **Complete Payment**
   - Open the redirect URL in browser
   - Use test card or Maya Wallet credentials
   - Complete payment

3. **Verify Payment**
   ```bash
   php artisan maya:test-payment --checkout-id=CHECKOUT_ID
   ```
   - Check payment status
   - Verify transaction details

## Expected Results

### Successful Payment
- Status: `PAYMENT_SUCCESS`
- Payment ID will be generated
- Payment token will be available
- Transaction metadata will be stored

### Failed Payment
- Status: `PAYMENT_FAILED`
- Error details in metadata
- Payment record marked as FAILED

### Pending Payment
- Status: `PENDING` or `CHECKOUT_PENDING`
- Payment not yet completed
- Can be verified again later

## Troubleshooting

### Credentials Not Set
If you see "Maya credentials not configured":
1. Check `.env` file exists
2. Verify credentials are added correctly
3. Run `php artisan config:clear` to refresh config

### Checkout Creation Fails
- Verify internet connection
- Check API keys are correct (Sandbox Party 2)
- Ensure sandbox mode is enabled

### Payment Verification Fails
- Checkout ID may be invalid
- Payment may not be completed yet
- Wait a few seconds and try again

## Integration Testing

To test the full integration flow:

1. Create a payment via the application
2. Process online payment (creates Maya checkout)
3. Complete payment using test card
4. Verify callback is received
5. Check payment status in database
6. Verify receipt is generated

## Notes

- All test cards use future expiry dates (12/2025)
- 3D Secure passwords are required for cards that have it enabled
- Maya Wallet test user always succeeds
- Sandbox environment does not process real payments
- Test checkout IDs start with the sandbox prefix



