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

Artisan::command('subscriptions:update-expired', function () {
    $updatedCount = \App\Models\MembershipSubscription::updateExpiredSubscriptions();
    
    if ($updatedCount === 0) {
        $this->info('No subscriptions needed expiration status update.');
        return 0;
    }

    $this->info("Successfully updated {$updatedCount} subscription(s) to EXPIRED status.");
    return 0;
})->purpose('Update subscription status to EXPIRED for subscriptions past their end_date');

Artisan::command('billing:generate-statements', function () {
    $this->info('Processing recurring billing statements...');
    $this->info('Checking for subscriptions expiring within 5 days...');
    
    try {
        // Instantiate and run the job synchronously
        $job = new \App\Jobs\ProcessRecurringBilling();
        $job->handle();
        
        $this->info('✓ Billing statement generation completed successfully.');
        $this->info('Check the application logs for details on generated statements.');
        return 0;
    } catch (\Exception $e) {
        $this->error('✗ Failed to generate billing statements: ' . $e->getMessage());
        \Log::error('Billing generation command failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return 1;
    }
})->purpose('Manually trigger billing statement generation for subscriptions expiring in 5 days');
