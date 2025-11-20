<?php

namespace App\Providers;

use App\Mail\Transport\MailtrapTransport;
use App\Services\MailtrapService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Register Mailtrap custom transport
        Mail::extend('mailtrap-sdk', function (array $config) {
            return new MailtrapTransport(
                app(MailtrapService::class)
            );
        });
    }
}
