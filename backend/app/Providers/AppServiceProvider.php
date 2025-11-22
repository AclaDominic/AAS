<?php

namespace App\Providers;

use App\Jobs\RetryFailedEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Events\MessageFailed;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Mailtrap\Bridge\Laravel\MailtrapSdkProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Mailtrap SDK Provider
        $this->app->register(MailtrapSdkProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Add logging for email sending events
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $message = $event->message;
            
            Log::info('Mailtrap: Email sending started', [
                'to' => array_map(fn($addr) => $addr->getAddress(), $message->getTo()),
                'subject' => $message->getSubject(),
                'from' => array_map(fn($addr) => $addr->getAddress(), $message->getFrom()),
                'has_html' => !empty($message->getHtmlBody()),
                'has_text' => !empty($message->getTextBody()),
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event) {
            $message = $event->message;
            
            Log::info('Mailtrap: Email sent successfully', [
                'to' => array_map(fn($addr) => $addr->getAddress(), $message->getTo()),
                'subject' => $message->getSubject(),
            ]);
        });

        // Auto-queue emails that fail to send immediately
        Event::listen(MessageFailed::class, function (MessageFailed $event) {
            $message = $event->message;
            $exception = $event->exception;
            $mailerName = $event->mailer ?? config('mail.default');
            
            Log::warning('Mailtrap: Email sending failed, queuing for retry', [
                'to' => array_map(fn($addr) => $addr->getAddress(), $message->getTo()),
                'subject' => $message->getSubject(),
                'error' => $exception->getMessage(),
                'mailer' => $mailerName,
            ]);

            // Extract email data for serialization
            try {
                $emailData = [
                    'to' => array_map(fn($addr) => [
                        'address' => $addr->getAddress(),
                        'name' => $addr->getName(),
                    ], iterator_to_array($message->getTo())),
                    'from' => array_map(fn($addr) => [
                        'address' => $addr->getAddress(),
                        'name' => $addr->getName(),
                    ], iterator_to_array($message->getFrom())),
                    'subject' => $message->getSubject() ?? '',
                    'html' => $message->getHtmlBody(),
                    'text' => $message->getTextBody(),
                ];
                
                // Dispatch a job to retry sending the email
                RetryFailedEmail::dispatch($emailData, $mailerName);
                
                Log::info('Mailtrap: Failed email queued for retry', [
                    'to' => array_map(fn($addr) => $addr->getAddress(), $message->getTo()),
                    'subject' => $message->getSubject(),
                ]);
            } catch (\Exception $e) {
                Log::error('Mailtrap: Failed to queue email for retry', [
                    'to' => array_map(fn($addr) => $addr->getAddress(), $message->getTo()),
                    'subject' => $message->getSubject(),
                    'queue_error' => $e->getMessage(),
                ]);
            }
        });
    }
}
