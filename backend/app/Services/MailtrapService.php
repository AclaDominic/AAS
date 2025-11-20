<?php

namespace App\Services;

use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

class MailtrapService
{
    protected string $apiKey;
    protected bool $isSandbox;
    protected int $inboxId;

    public function __construct()
    {
        $this->apiKey = env('MAILTRAP_API_KEY', '');
        $this->isSandbox = filter_var(env('MAILTRAP_SANDBOX', true), FILTER_VALIDATE_BOOLEAN);
        $this->inboxId = (int) env('MAILTRAP_INBOX_ID', 0);
    }

    /**
     * Send an email via Mailtrap API
     *
     * @param string $to
     * @param string $subject
     * @param string $text
     * @param string|null $html
     * @param string $fromEmail
     * @param string $fromName
     * @param string|null $category
     * @return array
     * @throws \Exception
     */
    public function send(
        string $to,
        string $subject,
        string $text,
        ?string $html = null,
        string $fromEmail = null,
        string $fromName = null,
        ?string $category = null
    ): array {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('MAILTRAP_API_KEY is not set in .env file. Please configure Mailtrap API key.');
        }

        if (empty($this->inboxId)) {
            throw new \RuntimeException('MAILTRAP_INBOX_ID is not set in .env file. Please configure Mailtrap inbox ID.');
        }

        $fromEmail = $fromEmail ?? config('mail.from.address', 'hello@example.com');
        $fromName = $fromName ?? config('mail.from.name', 'Mailtrap Test');

        try {
            $email = (new MailtrapEmail())
                ->from(new Address($fromEmail, $fromName))
                ->to(new Address($to))
                ->subject($subject)
                ->text($text);

            if ($html) {
                $email->html($html);
            }

            if ($category) {
                $email->category($category);
            }

            $response = MailtrapClient::initSendingEmails(
                apiKey: $this->apiKey,
                isSandbox: $this->isSandbox,
                inboxId: $this->inboxId
            )->send($email);

            return ResponseHelper::toArray($response);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Mailtrap email sending failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw as RuntimeException so queue can retry
            throw new \RuntimeException(
                'Failed to send email via Mailtrap: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}

