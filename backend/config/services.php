<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mailtrap-sdk' => [
        'host' => env('MAILTRAP_HOST', env('MAILTRAP_SANDBOX', false) ? 'sandbox.api.mailtrap.io' : 'send.api.mailtrap.io'),
        'apiKey' => env('MAILTRAP_API_KEY'),
        'inboxId' => env('MAILTRAP_INBOX_ID'),
        'sandbox' => (bool) env('MAILTRAP_SANDBOX', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maya Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Set these values in your .env file:
    |
    | MAYA_SANDBOX=true
    | MAYA_PUBLIC_KEY=pk-eo4sL393CWU5KmveJUaW8V730TTei2zY8zE4dHJDxkF
    | MAYA_SECRET_KEY=sk-KfmfLJXFdV5t1inYN8lIOwSrueC1G27SCAklBqYCdrU
    |
    | Sandbox Party 2 (CHECKOUT, VAULT, INVOICE enabled):
    | - The Maya Checkout page automatically displays all enabled payment methods
    | - Maya Wallet: Users can login with their Maya account to pay
    | - Credit/Debit Cards: Users can enter card details directly
    |
    */
    'maya' => [
        'sandbox' => env('MAYA_SANDBOX', true),
        'public_key' => env('MAYA_PUBLIC_KEY'),
        'secret_key' => env('MAYA_SECRET_KEY'),
        'base_url' => env('MAYA_SANDBOX', true) 
            ? 'https://pg-sandbox.paymaya.com' 
            : 'https://pg.paymaya.com',
    ],

];
