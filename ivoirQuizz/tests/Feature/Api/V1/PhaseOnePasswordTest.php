<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class PhaseOnePasswordTest extends TestCase
{
    public function test_reset_notification_uses_the_universal_https_link(): void
    {
        config(['auth_links.url' => 'https://laravelquizz.studiobeyam.net']);
        $user = User::factory()->create(['email' => 'awa@example.com']);
        $notification = new ResetPasswordNotification('secret-token');

        $url = $notification->toMail($user)->actionUrl;

        $this->assertStringStartsWith('https://laravelquizz.studiobeyam.net/auth/reset-password?', $url);
        $this->assertStringContainsString('token=secret-token', $url);
        $this->assertStringContainsString('email=awa%40example.com', $url);
    }

    public function test_forgot_password_has_same_response_for_known_and_unknown_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.com']);

        $known->assertOk()->assertJsonPath('success', true);
        $unknown->assertOk()->assertJsonPath('message', $known->json('message'));
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_stays_neutral_when_notification_dispatch_fails(): void
    {
        User::factory()->create(['email' => 'known@example.com']);
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable'));

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'known@example.com'])
            ->assertOk()->assertJsonPath('success', true);
    }

    public function test_user_can_reset_password_and_existing_tokens_are_revoked(): void
    {
        $user = User::factory()->create();
        $user->createToken('mobile');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('newPassword123', (string) $user->refresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_change_password_and_all_tokens_are_revoked(): void
    {
        $user = User::factory()->create(['password' => Hash::make('oldPassword123')]);
        $user->createToken('other-device');
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/password', [
            'current_password' => 'oldPassword123',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertOk();

        $this->assertTrue(Hash::check('newPassword123', (string) $user->refresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
