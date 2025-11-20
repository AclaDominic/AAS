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
    $apiKey = env('MAILTRAP_API_KEY');
    $isSandbox = env('MAILTRAP_SANDBOX', true);
    $inboxId = env('MAILTRAP_INBOX_ID');

    if (!$apiKey) {
        $this->error('MAILTRAP_API_KEY is not set in .env file');
        return 1;
    }

    if (!$inboxId) {
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
        apiKey: $apiKey,
        isSandbox: filter_var($isSandbox, FILTER_VALIDATE_BOOLEAN),
        inboxId: (int) $inboxId
    )->send($email);

    $this->info('Email sent successfully!');
    $this->line('Response:');
    $this->line(json_encode(ResponseHelper::toArray($response), JSON_PRETTY_PRINT));

    return 0;
})->purpose('Send Mail via Mailtrap API');
