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
        // Log email sending attempt
        \Log::info('MailtrapService: Starting email send', [
            'to' => $to,
            'subject' => $subject,
            'has_html' => !empty($html),
            'has_text' => !empty($text),
            'category' => $category,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
        ]);

        if (empty($this->apiKey)) {
            \Log::error('MailtrapService: MAILTRAP_API_KEY is not set');
            throw new \RuntimeException('MAILTRAP_API_KEY is not set in .env file. Please configure Mailtrap API key.');
        }

        if (empty($this->inboxId)) {
            \Log::error('MailtrapService: MAILTRAP_INBOX_ID is not set');
            throw new \RuntimeException('MAILTRAP_INBOX_ID is not set in .env file. Please configure Mailtrap inbox ID.');
        }

        $fromEmail = $fromEmail ?? config('mail.from.address', 'hello@example.com');
        $fromName = $fromName ?? config('mail.from.name', 'Mailtrap Test');

        // Log configuration before API call (sanitize API key)
        \Log::info('MailtrapService: Preparing to send email via API', [
            'inbox_id' => $this->inboxId,
            'is_sandbox' => $this->isSandbox,
            'api_key_set' => !empty($this->apiKey),
            'api_key_length' => strlen($this->apiKey),
        ]);

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

            $responseArray = ResponseHelper::toArray($response);

            // Log successful send
            \Log::info('MailtrapService: Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'response' => $responseArray,
            ]);

            return $responseArray;
        } catch (\Exception $e) {
            // Enhanced error logging with more context
            \Log::error('MailtrapService: Email sending failed', [
                'to' => $to,
                'subject' => $subject,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'has_html' => !empty($html),
                'has_text' => !empty($text),
                'category' => $category,
                'inbox_id' => $this->inboxId,
                'is_sandbox' => $this->isSandbox,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
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

