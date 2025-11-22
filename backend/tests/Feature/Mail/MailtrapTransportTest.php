<?php

namespace Tests\Feature\Mail;

use App\Mail\Transport\MailtrapTransport;
use App\Services\MailtrapService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class MailtrapTransportTest extends TestCase
{

    protected function tearDown(): void
    {
        putenv('MAILTRAP_API_KEY');
        putenv('MAILTRAP_INBOX_ID');
        putenv('MAILTRAP_SANDBOX');
        
        parent::tearDown();
    }

    public function test_transport_requires_from_and_to_addresses(): void
    {
        $mailtrapService = \Mockery::mock(MailtrapService::class);
        $transport = new MailtrapTransport($mailtrapService);
        
        $email = new Email();
        // Don't set from or to addresses
        $email->text('Test body'); // Email needs a body
        
        $envelope = new Envelope(
            new Address('from@example.com'),
            [new Address('to@example.com')]
        );
        $sentMessage = new SentMessage($email, $envelope);
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Starting doSend()', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: Missing from or to addresses', \Mockery::type('array'))
            ->once();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email must have from and to addresses');
        
        // Use reflection to call doSend() directly
        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->setAccessible(true);
        $method->invoke($transport, $sentMessage);
    }

    public function test_transport_converts_email_and_logs_recipients(): void
    {
        putenv('MAILTRAP_API_KEY=test-api-key');
        putenv('MAILTRAP_INBOX_ID=123456');
        putenv('MAILTRAP_SANDBOX=true');
        
        $mailtrapService = \Mockery::mock(MailtrapService::class);
        $mailtrapService->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('API Error'));
        
        $transport = new MailtrapTransport($mailtrapService);
        
        $email = (new Email())
            ->from(new Address('from@example.com', 'From Name'))
            ->to(new Address('to@example.com', 'To Name'))
            ->subject('Test Subject')
            ->text('Test body');
        
        $envelope = new Envelope(
            new Address('from@example.com'),
            [new Address('to@example.com')]
        );
        $sentMessage = new SentMessage($email, $envelope);
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Starting doSend()', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Email converted, processing recipients', function ($context) {
                return isset($context['from']) 
                    && isset($context['subject'])
                    && isset($context['recipient_count']);
            })
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Processing recipient', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: RuntimeException while sending to recipient', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: Failed to send email', \Mockery::type('array'))
            ->once();
        
        $this->expectException(\RuntimeException::class);
        
        // Use reflection to call doSend() directly
        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->setAccessible(true);
        $method->invoke($transport, $sentMessage);
    }

    public function test_transport_handles_multiple_recipients(): void
    {
        putenv('MAILTRAP_API_KEY=test-api-key');
        putenv('MAILTRAP_INBOX_ID=123456');
        putenv('MAILTRAP_SANDBOX=true');
        
        $mailtrapService = \Mockery::mock(MailtrapService::class);
        $mailtrapService->shouldReceive('send')
            ->twice()
            ->andThrow(new \RuntimeException('API Error'));
        
        $transport = new MailtrapTransport($mailtrapService);
        
        $email = (new Email())
            ->from(new Address('from@example.com', 'From Name'))
            ->to(new Address('to1@example.com', 'To 1'))
            ->to(new Address('to2@example.com', 'To 2'))
            ->subject('Test Subject')
            ->text('Test body');
        
        $envelope = new Envelope(
            new Address('from@example.com'),
            [new Address('to@example.com')]
        );
        $sentMessage = new SentMessage($email, $envelope);
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Starting doSend()', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Email converted, processing recipients', function ($context) {
                return isset($context['recipient_count']) && $context['recipient_count'] === 2;
            })
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Processing recipient', \Mockery::type('array'))
            ->twice();
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: RuntimeException while sending to recipient', \Mockery::type('array'))
            ->once(); // First recipient fails, so we stop
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: Failed to send email', \Mockery::type('array'))
            ->once();
        
        $this->expectException(\RuntimeException::class);
        
        // Use reflection to call doSend() directly
        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->setAccessible(true);
        $method->invoke($transport, $sentMessage);
    }

    public function test_transport_handles_html_and_text_bodies(): void
    {
        putenv('MAILTRAP_API_KEY=test-api-key');
        putenv('MAILTRAP_INBOX_ID=123456');
        putenv('MAILTRAP_SANDBOX=true');
        
        $mailtrapService = \Mockery::mock(MailtrapService::class);
        $mailtrapService->shouldReceive('send')
            ->once()
            ->with(
                'to@example.com',
                'Test Subject',
                'Test text body',
                '<html>Test HTML</html>',
                'from@example.com',
                'From Name',
                null
            )
            ->andThrow(new \RuntimeException('API Error'));
        
        $transport = new MailtrapTransport($mailtrapService);
        
        $email = (new Email())
            ->from(new Address('from@example.com', 'From Name'))
            ->to(new Address('to@example.com', 'To Name'))
            ->subject('Test Subject')
            ->text('Test text body')
            ->html('<html>Test HTML</html>');
        
        $envelope = new Envelope(
            new Address('from@example.com'),
            [new Address('to@example.com')]
        );
        $sentMessage = new SentMessage($email, $envelope);
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Starting doSend()', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Email converted, processing recipients', function ($context) {
                return isset($context['has_text_body']) 
                    && isset($context['has_html_body'])
                    && $context['has_text_body'] === true
                    && $context['has_html_body'] === true;
            })
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Processing recipient', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: RuntimeException while sending to recipient', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: Failed to send email', \Mockery::type('array'))
            ->once();
        
        $this->expectException(\RuntimeException::class);
        
        // Use reflection to call doSend() directly
        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->setAccessible(true);
        $method->invoke($transport, $sentMessage);
    }

    public function test_transport_converts_html_to_text_when_no_text_body(): void
    {
        putenv('MAILTRAP_API_KEY=test-api-key');
        putenv('MAILTRAP_INBOX_ID=123456');
        putenv('MAILTRAP_SANDBOX=true');
        
        $mailtrapService = \Mockery::mock(MailtrapService::class);
        $mailtrapService->shouldReceive('send')
            ->once()
            ->with(
                'to@example.com',
                'Test Subject',
                'Test HTML', // Should be stripped from HTML
                '<html><body>Test HTML</body></html>',
                'from@example.com',
                'From Name',
                null
            )
            ->andThrow(new \RuntimeException('API Error'));
        
        $transport = new MailtrapTransport($mailtrapService);
        
        $email = (new Email())
            ->from(new Address('from@example.com', 'From Name'))
            ->to(new Address('to@example.com', 'To Name'))
            ->subject('Test Subject')
            ->html('<html><body>Test HTML</body></html>');
        // No text body
        
        $envelope = new Envelope(
            new Address('from@example.com'),
            [new Address('to@example.com')]
        );
        $sentMessage = new SentMessage($email, $envelope);
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Starting doSend()', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Email converted, processing recipients', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Processing recipient', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: RuntimeException while sending to recipient', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('error')
            ->with('MailtrapTransport: Failed to send email', \Mockery::type('array'))
            ->once();
        
        $this->expectException(\RuntimeException::class);
        
        // Use reflection to call doSend() directly
        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->setAccessible(true);
        $method->invoke($transport, $sentMessage);
    }

    public function test_transport_logs_successful_send(): void
    {
        putenv('MAILTRAP_API_KEY=test-api-key');
        putenv('MAILTRAP_INBOX_ID=123456');
        putenv('MAILTRAP_SANDBOX=true');
        
        $mailtrapService = \Mockery::mock(MailtrapService::class);
        $mailtrapService->shouldReceive('send')
            ->once()
            ->andReturn(['success' => true, 'message_ids' => ['123']]);
        
        $transport = new MailtrapTransport($mailtrapService);
        
        $email = (new Email())
            ->from(new Address('from@example.com', 'From Name'))
            ->to(new Address('to@example.com', 'To Name'))
            ->subject('Test Subject')
            ->text('Test body');
        
        $envelope = new Envelope(
            new Address('from@example.com'),
            [new Address('to@example.com')]
        );
        $sentMessage = new SentMessage($email, $envelope);
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Starting doSend()', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Email converted, processing recipients', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Processing recipient', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapService: Starting email send', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapService: Preparing to send email via API', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapService: Email sent successfully', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Successfully sent email to recipient', \Mockery::type('array'))
            ->once();
        
        Log::shouldReceive('info')
            ->with('MailtrapTransport: Successfully processed all recipients', \Mockery::type('array'))
            ->once();
        
        // Use reflection to call doSend() directly
        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->setAccessible(true);
        $method->invoke($transport, $sentMessage);
        
        $this->assertTrue(true); // If we get here, the test passed
    }

    public function test_transport_to_string_returns_mailtrap_sdk(): void
    {
        $mailtrapService = \Mockery::mock(MailtrapService::class);
        $transport = new MailtrapTransport($mailtrapService);
        
        $this->assertEquals('mailtrap-sdk', (string) $transport);
    }
}

