<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Se connecter');
    }

    public function test_admin_can_log_in_and_out(): void
    {
        $admin = User::factory()->create(['email' => 'admin@ivoire.test', 'is_admin' => true]);

        $this->post('/admin/login', ['email' => 'admin@ivoire.test', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->last_login_at);

        $this->get('/admin')->assertOk()->assertSee('Tableau de bord');

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_regular_player_cannot_log_in_to_the_back_office(): void
    {
        User::factory()->create(['email' => 'player@ivoire.test']);

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'player@ivoire.test', 'password' => 'password'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_admin_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'old-admin@ivoire.test', 'is_admin' => true, 'status' => 'suspended']);

        $this->post('/admin/login', ['email' => 'old-admin@ivoire.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_session_of_a_player_without_admin_rights_is_closed(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)->get('/admin/users')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_admin_whose_rights_were_removed_loses_access_immediately(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin')->assertOk();

        $admin->forceFill(['is_admin' => false])->save();

        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $this->freezeTime();
        User::factory()->create(['email' => 'admin@ivoire.test', 'is_admin' => true]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/admin/login', ['email' => 'admin@ivoire.test', 'password' => 'wrong-'.$attempt]);
        }

        $this->post('/admin/login', ['email' => 'admin@ivoire.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Trop de tentatives. Réessayez dans 60 secondes.']);
        $this->assertGuest();
    }

    public function test_api_routes_are_not_affected_by_the_admin_login_redirect(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('success', false);
    }
}
