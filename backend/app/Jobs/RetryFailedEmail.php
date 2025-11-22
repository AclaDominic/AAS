<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RetryFailedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 minute, 5 minutes, 15 minutes

    public array $emailData;
    public string $mailer;

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData, string $mailer = null)
    {
        $this->emailData = $emailData;
        $this->mailer = $mailer ?? config('mail.default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('RetryFailedEmail: Attempting to resend email', [
            'to' => array_column($this->emailData['to'], 'address'),
            'subject' => $this->emailData['subject'],
            'attempt' => $this->attempts(),
        ]);

        try {
            $mailer = Mail::mailer($this->mailer);
            
            $mailer->send([], [], function ($m) {
                // Set recipients
                foreach ($this->emailData['to'] as $to) {
                    $m->to($to['address'], $to['name'] ?? null);
                }
                
                // Set from
                foreach ($this->emailData['from'] as $from) {
                    $m->from($from['address'], $from['name'] ?? null);
                }
                
                // Set subject
                if ($this->emailData['subject']) {
                    $m->subject($this->emailData['subject']);
                }
                
                // Set body
                if (!empty($this->emailData['html'])) {
                    $m->html($this->emailData['html']);
                }
                
                if (!empty($this->emailData['text'])) {
                    $m->text($this->emailData['text']);
                }
            });
            
            Log::info('RetryFailedEmail: Email resent successfully', [
                'to' => array_column($this->emailData['to'], 'address'),
                'subject' => $this->emailData['subject'],
            ]);
        } catch (\Exception $e) {
            Log::error('RetryFailedEmail: Failed to resend email', [
                'to' => array_column($this->emailData['to'], 'address'),
                'subject' => $this->emailData['subject'],
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }
}
