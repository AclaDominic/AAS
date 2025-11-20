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
        try {
            $email = MessageConverter::toEmail($message->getOriginalMessage());
            
            $from = $email->getFrom()[0] ?? null;
            $toRecipients = $email->getTo();
            
            if (!$from || empty($toRecipients)) {
                throw new \RuntimeException('Email must have from and to addresses');
            }
            
            // Send to all recipients
            foreach ($toRecipients as $to) {
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
                        to: $to->getAddress(),
                        subject: $email->getSubject() ?? '',
                        text: $text,
                        html: $html,
                        fromEmail: $from->getAddress(),
                        fromName: $from->getName() ?? '',
                    );
                } catch (\RuntimeException $e) {
                    // Re-throw RuntimeExceptions (connection errors, config errors) for queue retry
                    throw $e;
                } catch (\Exception $e) {
                    // Wrap other exceptions as RuntimeException for queue retry
                    throw new \RuntimeException(
                        'Failed to send email via Mailtrap transport: ' . $e->getMessage(),
                        $e->getCode(),
                        $e
                    );
                }
            }
        } catch (\RuntimeException $e) {
            // Log the error
            \Log::error('MailtrapTransport failed to send email', [
                'error' => $e->getMessage(),
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

