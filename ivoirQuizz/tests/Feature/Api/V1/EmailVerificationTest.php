<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    public function test_registration_sends_verification_without_blocking_access(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa', 'username' => 'awa', 'email' => 'awa@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonPath('data.user.email_verified_at', null)
            ->assertJsonStructure(['data' => ['token']]);
        Notification::assertSentTo(User::where('email', 'awa@example.com')->firstOrFail(), VerifyEmailNotification::class);
    }

    public function test_legacy_mobile_registration_also_sends_verification(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'name' => 'Koffi', 'email' => 'koffi@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated();

        Notification::assertSentTo(User::where('email', 'koffi@example.com')->firstOrFail(), VerifyEmailNotification::class);
    }

    public function test_resend_response_is_neutral(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['email' => 'known@example.com']);

        $known = $this->postJson('/api/v1/auth/email/verification-notification', ['email' => $user->email]);
        $unknown = $this->postJson('/api/v1/auth/email/verification-notification', ['email' => 'unknown@example.com']);

        $known->assertOk();
        $unknown->assertOk()->assertJsonPath('message', $known->json('message'));
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resend_stays_neutral_when_notification_dispatch_fails(): void
    {
        User::factory()->unverified()->create(['email' => 'known@example.com']);
        Notification::shouldReceive('sendNow')->once()->andThrow(new RuntimeException('SMTP unavailable'));

        $this->postJson('/api/v1/auth/email/verification-notification', ['email' => 'known@example.com'])
            ->assertOk()->assertJsonPath('success', true);
    }

    public function test_verification_notification_uses_canonical_auth_link_origin(): void
    {
        config(['app.url' => 'http://localhost', 'auth_links.url' => 'https://laravelquizz.studiobeyam.net']);
        $user = User::factory()->unverified()->create();

        $url = (new VerifyEmailNotification)->toMail($user)->actionUrl;

        $this->assertStringStartsWith('https://laravelquizz.studiobeyam.net/auth/verify-email/', $url);
    }

    public function test_signed_link_verifies_email_and_is_idempotent(): void
    {
        $user = User::factory()->unverified()->create();
        $url = (new VerifyEmailNotification)->toMail($user)->actionUrl;

        $this->get($url)->assertOk();
        $this->assertNotNull($user->refresh()->email_verified_at);
        $this->get($url)->assertOk();
    }

    public function test_invalid_signature_does_not_verify_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get('/auth/verify-email/'.$user->id.'/'.sha1($user->email).'?signature=invalid')
            ->assertForbidden();
        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_mobile_association_endpoints_are_valid_when_identifiers_are_missing(): void
    {
        $this->getJson('/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);
    }
}
