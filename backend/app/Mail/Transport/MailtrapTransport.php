<?php

namespace App\Mail\Transport;

use App\Services\MailtrapService;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class MailtrapTransport extends AbstractTransport
{
    protected MailtrapService $mailtrapService;

    public function __construct(MailtrapService $mailtrapService)
    {
        parent::__construct();
        $this->mailtrapService = $mailtrapService;
    }

    protected function doSend(SentMessage $message): void
    {
        // Log start of email sending process
        \Log::info('MailtrapTransport: Starting doSend()', [
            'message_id' => $message->getMessageId(),
        ]);

        try {
            $email = MessageConverter::toEmail($message->getOriginalMessage());
            
            $from = $email->getFrom()[0] ?? null;
            $toRecipients = $email->getTo();
            
            if (!$from || empty($toRecipients)) {
                \Log::error('MailtrapTransport: Missing from or to addresses', [
                    'has_from' => !is_null($from),
                    'recipient_count' => count($toRecipients),
                ]);
                throw new \RuntimeException('Email must have from and to addresses');
            }

            \Log::info('MailtrapTransport: Email converted, processing recipients', [
                'from' => $from->getAddress(),
                'from_name' => $from->getName(),
                'subject' => $email->getSubject(),
                'recipient_count' => count($toRecipients),
                'has_text_body' => !empty($email->getTextBody()),
                'has_html_body' => !empty($email->getHtmlBody()),
            ]);
            
            // Send to all recipients
            foreach ($toRecipients as $to) {
                $recipientAddress = $to->getAddress();
                
                \Log::info('MailtrapTransport: Processing recipient', [
                    'recipient' => $recipientAddress,
                    'recipient_name' => $to->getName(),
                ]);

                $text = null;
                $html = null;
                
                if ($email->getTextBody()) {
                    $text = $email->getTextBody();
                }
                
                if ($email->getHtmlBody()) {
                    $html = $email->getHtmlBody();
                }
                
                // If no text body, use HTML as text
                if (!$text && $html) {
                    $text = strip_tags($html);
                }
                
                // If no HTML and no text, use empty string
                if (!$text) {
                    $text = '';
                }

                try {
                    $this->mailtrapService->send(
                        to: $recipientAddress,
                        subject: $email->getSubject() ?? '',
                        text: $text,
                        html: $html,
                        fromEmail: $from->getAddress(),
                        fromName: $from->getName() ?? '',
                    );

                    \Log::info('MailtrapTransport: Successfully sent email to recipient', [
                        'recipient' => $recipientAddress,
                        'subject' => $email->getSubject(),
                    ]);
                } catch (\RuntimeException $e) {
                    \Log::error('MailtrapTransport: RuntimeException while sending to recipient', [
                        'recipient' => $recipientAddress,
                        'subject' => $email->getSubject(),
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                    ]);
                    // Re-throw RuntimeExceptions (connection errors, config errors) for queue retry
                    throw $e;
                } catch (\Exception $e) {
                    \Log::error('MailtrapTransport: Exception while sending to recipient', [
                        'recipient' => $recipientAddress,
                        'subject' => $email->getSubject(),
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_class' => get_class($e),
                    ]);
                    // Wrap other exceptions as RuntimeException for queue retry
                    throw new \RuntimeException(
                        'Failed to send email via Mailtrap transport: ' . $e->getMessage(),
                        $e->getCode(),
                        $e
                    );
                }
            }

            \Log::info('MailtrapTransport: Successfully processed all recipients', [
                'recipient_count' => count($toRecipients),
            ]);
        } catch (\RuntimeException $e) {
            // Enhanced error logging with email details
            \Log::error('MailtrapTransport: Failed to send email', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
                'message_id' => $message->getMessageId(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw so queue can retry
            throw $e;
        }
    }

    public function __toString(): string
    {
        return 'mailtrap-sdk';
    }
}

