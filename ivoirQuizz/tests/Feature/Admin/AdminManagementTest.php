<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\City;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Region;
use App\Models\User;
use App\Services\Game\GameCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
    }

    public function test_balance_adjustment_is_recorded_as_a_reward_transaction(): void
    {
        $player = User::factory()->create(['coins' => 50, 'gems' => 2]);

        $this->post(route('admin.users.balance', $player), ['currency' => 'coin', 'amount' => -20, 'reason' => 'Correction'])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.users.balance', $player), ['currency' => 'gem', 'amount' => 5, 'reason' => 'Compensation'])
            ->assertSessionHasNoErrors();

        $player->refresh();
        $this->assertSame(30, $player->coins);
        $this->assertSame(7, $player->gems);
        $this->assertDatabaseHas('reward_transactions', [
            'user_id' => $player->id, 'type' => 'coin', 'amount' => -20, 'source_type' => 'admin', 'source_id' => $this->admin->id, 'description' => 'Correction',
        ]);
    }

    public function test_balance_cannot_become_negative(): void
    {
        $player = User::factory()->create(['coins' => 10]);

        $this->post(route('admin.users.balance', $player), ['currency' => 'coin', 'amount' => -11, 'reason' => 'Trop'])
            ->assertSessionHasErrors('amount');

        $this->assertSame(10, $player->fresh()->coins);
        $this->assertDatabaseCount('reward_transactions', 0);
    }

    public function test_suspending_a_player_revokes_their_mobile_tokens(): void
    {
        $player = User::factory()->create();
        $player->createToken('mobile');

        $this->patch(route('admin.users.status', $player), ['status' => 'suspended'])->assertSessionHasNoErrors();

        $this->assertSame(UserStatus::Suspended, $player->fresh()->status);
        $this->assertSame(0, $player->tokens()->count());
    }

    public function test_admin_cannot_change_their_own_status_rights_or_delete_themselves(): void
    {
        $this->patch(route('admin.users.status', $this->admin), ['status' => 'disabled'])->assertForbidden();
        $this->patch(route('admin.users.admin', $this->admin))->assertForbidden();
        $this->delete(route('admin.users.destroy', $this->admin))->assertForbidden();

        $this->assertTrue($this->admin->fresh()->is_admin);
        $this->assertSame(UserStatus::Active, $this->admin->fresh()->status);
    }

    public function test_admin_rights_can_be_granted_and_removed(): void
    {
        $player = User::factory()->create();

        $this->patch(route('admin.users.admin', $player));
        $this->assertTrue($player->fresh()->is_admin);

        $this->patch(route('admin.users.admin', $player));
        $this->assertFalse($player->fresh()->is_admin);
    }

    public function test_profile_update_cannot_grant_admin_rights_or_change_progression(): void
    {
        $player = User::factory()->create(['xp_total' => 100]);

        $this->put(route('admin.users.update', $player), [
            'name' => 'Koffi',
            'email' => 'koffi@ivoire.test',
            'username' => 'koffi_ci',
            'is_admin' => '1',
            'xp_total' => 999999,
        ])->assertRedirect(route('admin.users.show', $player));

        $player->refresh();
        $this->assertSame('Koffi', $player->name);
        $this->assertFalse($player->is_admin);
        $this->assertSame(100, $player->xp_total);
    }

    public function test_deleting_a_player_anonymizes_the_account(): void
    {
        $player = User::factory()->create(['email' => 'secret@ivoire.test']);

        $this->delete(route('admin.users.destroy', $player))->assertRedirect(route('admin.users.index'));

        $deleted = User::withTrashed()->findOrFail($player->id);
        $this->assertSoftDeleted($deleted);
        $this->assertSame('Compte supprimé', $deleted->name);
        $this->assertStringEndsWith('@example.invalid', $deleted->email);
    }

    public function test_region_slug_is_generated_and_map_cache_is_cleared(): void
    {
        Storage::fake('public');
        Cache::put(GameCacheService::REGIONS_MAP_CACHE_KEY, ['stale'], 3600);

        $this->post(route('admin.regions.store'), [
            'name' => 'Vallée du Bandama',
            'order' => 4,
            'required_xp' => 0,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('bandama.jpg'),
        ])->assertRedirect(route('admin.regions.index'));

        $region = Region::query()->sole();
        $this->assertSame('vallee-du-bandama', $region->slug);
        Storage::disk('public')->assertExists($region->image);
        $this->assertFalse(Cache::has(GameCacheService::REGIONS_MAP_CACHE_KEY));
    }

    public function test_city_slug_must_be_unique_within_its_region_only(): void
    {
        $north = Region::query()->create(['name' => 'Nord', 'slug' => 'nord']);
        $south = Region::query()->create(['name' => 'Sud', 'slug' => 'sud']);
        City::query()->create(['region_id' => $north->id, 'name' => 'Centre', 'slug' => 'centre']);

        $this->post(route('admin.cities.store'), ['region_id' => $north->id, 'name' => 'Centre', 'order' => 1, 'required_xp' => 0])
            ->assertSessionHasErrors('slug');
        $this->post(route('admin.cities.store'), ['region_id' => $south->id, 'name' => 'Centre', 'order' => 1, 'required_xp' => 0])
            ->assertSessionHasNoErrors();
    }

    public function test_content_with_children_cannot_be_deleted(): void
    {
        $region = Region::query()->create(['name' => 'Nord', 'slug' => 'nord']);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Korhogo', 'slug' => 'korhogo']);
        $level = Level::query()->create(['city_id' => $city->id, 'title' => 'N1', 'slug' => 'n1']);
        Question::query()->create(['level_id' => $level->id, 'question_text' => 'Q ?']);

        $this->delete(route('admin.regions.destroy', $region))->assertSessionHas('error');
        $this->delete(route('admin.cities.destroy', $city))->assertSessionHas('error');
        $this->delete(route('admin.levels.destroy', $level))->assertSessionHas('error');

        $this->assertNotSoftDeleted($region);
        $this->assertNotSoftDeleted($city);
        $this->assertNotSoftDeleted($level);
    }

    public function test_level_validation_rejects_invalid_rules(): void
    {
        $region = Region::query()->create(['name' => 'Nord', 'slug' => 'nord']);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Korhogo', 'slug' => 'korhogo']);

        $this->post(route('admin.levels.store'), [
            'city_id' => $city->id,
            'title' => 'Niveau',
            'difficulty' => 'impossible',
            'order' => 1,
            'required_xp' => 0,
            'questions_count' => 0,
            'passing_score' => 150,
            'xp_reward' => 10,
            'coins_reward' => 0,
            'gems_reward' => 0,
        ])->assertSessionHasErrors(['difficulty', 'questions_count', 'passing_score']);
    }

    public function test_resolving_a_report_can_close_the_other_pending_reports_on_the_question(): void
    {
        $region = Region::query()->create(['name' => 'Nord', 'slug' => 'nord']);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Korhogo', 'slug' => 'korhogo']);
        $level = Level::query()->create(['city_id' => $city->id, 'title' => 'N1', 'slug' => 'n1']);
        $question = Question::query()->create(['level_id' => $level->id, 'question_text' => 'Q ?']);
        $reporters = User::factory()->count(3)->create();
        [$first, $second, $third] = $reporters->map(fn (User $user) => QuestionReport::query()->create([
            'user_id' => $user->id, 'question_id' => $question->id, 'reason' => 'typo',
        ]))->all();
        $third->update(['status' => 'rejected']);

        $this->patch(route('admin.reports.update', $first), ['status' => 'fixed', 'apply_to_question' => '1'])
            ->assertRedirect(route('admin.reports.index'));

        $this->assertSame('fixed', $first->fresh()->status);
        $this->assertNotNull($first->fresh()->reviewed_at);
        $this->assertSame('fixed', $second->fresh()->status);
        $this->assertSame('rejected', $third->fresh()->status);
    }

    public function test_chest_maximum_must_not_be_lower_than_minimum(): void
    {
        $this->post(route('admin.chests.store'), [
            'name' => 'Coffre test',
            'type' => 'gold',
            'min_xp' => 50, 'max_xp' => 10,
            'min_coins' => 0, 'max_coins' => 0,
            'min_gems' => 0, 'max_gems' => 0,
        ])->assertSessionHasErrors('max_xp');
    }

    public function test_admin_create_command_promotes_an_existing_player(): void
    {
        $player = User::factory()->create(['email' => 'chef@ivoire.test', 'status' => 'suspended']);

        $this->artisan('admin:create', ['email' => 'chef@ivoire.test'])->assertSuccessful();

        $player->refresh();
        $this->assertTrue($player->is_admin);
        $this->assertSame(UserStatus::Active, $player->status);
    }

    public function test_admin_create_command_creates_an_account_with_a_strong_password(): void
    {
        $this->artisan('admin:create', ['email' => 'new@ivoire.test', '--password' => 'court'])->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'new@ivoire.test']);

        $this->artisan('admin:create', ['email' => 'new@ivoire.test', '--name' => 'Nouvel Admin', '--password' => 'un-mot-de-passe-solide'])->assertSuccessful();

        $this->post(route('admin.logout'));
        $this->post(route('admin.login.store'), ['email' => 'new@ivoire.test', 'password' => 'un-mot-de-passe-solide'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
