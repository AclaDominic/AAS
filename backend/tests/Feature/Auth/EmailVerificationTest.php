<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(config('app.frontend_url').'/dashboard?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_email_is_sent_when_user_registers(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertStatus(201);
    }

    public function test_verification_email_notification_has_correct_content(): void
    {
        $user = User::factory()->unverified()->create();

        $notification = new VerifyEmail();
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Verify Email Address', $mailMessage->subject);
        $this->assertStringContainsString('Please click the button below to verify your email address.', $mailMessage->introLines[0]);
        $this->assertStringContainsString('If you did not create an account, no further action is required.', $mailMessage->outroLines[0]);
    }

    public function test_verification_email_contains_valid_verification_url(): void
    {
        $user = User::factory()->unverified()->create();

        $notification = new VerifyEmail();
        $mailMessage = $notification->toMail($user);

        // Get the action URL from the mail message
        $actionUrl = $mailMessage->actionUrl;

        // Verify the URL contains the user ID and hash
        $this->assertStringContainsString((string) $user->id, $actionUrl);
        $this->assertStringContainsString(sha1($user->email), $actionUrl);
    }
}
