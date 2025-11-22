<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('send-mail', function () {
    $config = config('services.mailtrap-sdk');
    
    if (!$config['apiKey']) {
        $this->error('MAILTRAP_API_KEY is not set in .env file');
        return 1;
    }

    if (!$config['inboxId']) {
        $this->error('MAILTRAP_INBOX_ID is not set in .env file');
        return 1;
    }

    $email = (new MailtrapEmail())
        ->from(new Address('hello@example.com', 'Mailtrap Test'))
        ->to(new Address('acladominic10@gmail.com'))
        ->subject('You are awesome!')
        ->category('Integration Test')
        ->text('Congrats for sending test email with Mailtrap!')
    ;

    $response = MailtrapClient::initSendingEmails(
        apiKey: $config['apiKey'],
        isSandbox: $config['sandbox'],
        inboxId: (int) $config['inboxId']
    )->send($email);

    $this->info('Email sent successfully!');
    $this->line('Response:');
    $this->line(json_encode(ResponseHelper::toArray($response), JSON_PRETTY_PRINT));

    return 0;
})->purpose('Send Mail via Mailtrap API');

Artisan::command('payments:cancel-old', function () {
    $cutoffDate = now()->subDays(15);
    
    $count = \App\Models\Payment::where('status', 'PENDING')
        ->where('created_at', '<', $cutoffDate)
        ->count();

    if ($count === 0) {
        $this->info('No pending payments older than 15 days found.');
        return 0;
    }

    $updated = \App\Models\Payment::where('status', 'PENDING')
        ->where('created_at', '<', $cutoffDate)
        ->update([
            'status' => 'CANCELLED',
            'payment_code' => null, // Clear payment code when auto-cancelled
        ]);

    $this->info("Successfully cancelled {$updated} pending payment(s) older than 15 days.");

    return 0;
})->purpose('Cancel pending payments older than 15 days');
