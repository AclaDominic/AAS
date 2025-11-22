<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Don't apply stateful middleware to API routes - they use token auth
        // $middleware->api(prepend: [
        //     \App\Http\Middleware\ConditionalStatefulMiddleware::class,
        // ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // Exclude auth routes from CSRF verification since we're using token-based auth
        $middleware->validateCsrfTokens(except: [
            'login',
            'register',
            'logout',
            'forgot-password',
            'reset-password',
            'email/verification-notification',
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Update expired subscriptions daily at midnight
        $schedule->command('subscriptions:update-expired')
            ->daily()
            ->at('00:00');
        
        // Process recurring billing daily at 1:00 AM (check for subscriptions expiring in 5 days)
        // This automatically generates billing statements and sends notifications
        $schedule->job(\App\Jobs\ProcessRecurringBilling::class)
            ->daily()
            ->at('01:00')
            ->name('process-recurring-billing')
            ->withoutOverlapping();
        
        // Automatically cancel pending payments older than 15 days
        $schedule->call(function () {
            \App\Models\Payment::where('status', 'PENDING')
                ->where('created_at', '<', now()->subDays(15))
                ->update([
                    'status' => 'CANCELLED',
                    'payment_code' => null, // Clear payment code when auto-cancelled
                ]);
        })->daily()->at('02:00'); // Run daily at 2:00 AM
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
