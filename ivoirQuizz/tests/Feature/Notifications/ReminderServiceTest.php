<?php

namespace Tests\Feature\Notifications;

use App\Models\City;
use App\Models\Level;
use App\Models\PushAutomation;
use App\Models\PushLog;
use App\Models\Question;
use App\Models\Region;
use App\Models\UserLife;
use App\Models\UserStreak;
use App\Services\Notifications\PushCatalog;
use App\Services\Notifications\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesFcm;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use FakesFcm, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeFcm();
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeFcm();
        parent::tearDown();
    }

    public function test_morning_reminder_follows_each_player_journey(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 08:00'));
        $old = ['created_at' => now()->subDays(60)];

        $this->playerWithDevice('new', ['name' => 'Aya Koné', 'created_at' => now()->subDay()]);
        $streaker = $this->playerWithDevice('streak', [...$old, 'last_activity_date' => today()->subDay()]);
        UserStreak::query()->create(['user_id' => $streaker->id, 'current_streak' => 5, 'last_played_date' => today()->subDay()]);
        $this->playerWithDevice('regular', [...$old, 'name' => 'Koffi Kouassi', 'last_activity_date' => today()->subDays(2)]);
        $this->playerWithDevice('today', [...$old, 'last_activity_date' => today()]);
        $this->playerWithDevice('missed', [...$old, 'name' => 'Yao Konan', 'last_activity_date' => today()->subDays(10)]);
        $recentlyRemindedPlayer = $this->playerWithDevice('missed-recently', [...$old, 'last_activity_date' => today()->subDays(10)]);
        PushLog::query()->create(['user_id' => $recentlyRemindedPlayer->id, 'type' => 'morning', 'is_reminder' => true, 'created_at' => now()->subDay()]);
        $this->playerWithDevice('dormant', [...$old, 'last_activity_date' => today()->subDays(40)]);
        $this->playerWithDevice('gone', ['created_at' => now()->subDays(200), 'last_activity_date' => today()->subDays(100)]);
        $this->playerWithDevice('suspended', [...$old, 'status' => 'suspended', 'last_activity_date' => today()->subDays(2)]);

        $this->assertSame(['morning' => 5], array_filter(app(ReminderService::class)->tick()));

        $this->assertSame('Bienvenue dans IvoireQuizz 🇨🇮', $this->messageFor('new')['notification']['title']);
        $this->assertSame('🔥 Série de 5 jours', $this->messageFor('streak')['notification']['title']);
        $this->assertSame('Bonjour Koffi ☀️', $this->messageFor('regular')['notification']['title']);
        $this->assertSame(['type' => 'daily_reminder'], $this->messageFor('regular')['data']);
        $this->assertSame('Tu nous manques, Yao', $this->messageFor('missed')['notification']['title']);
        $this->assertStringStartsWith('On ne t’oublie pas', $this->messageFor('dormant')['notification']['title']);
        foreach (['today', 'missed-recently', 'gone', 'suspended'] as $token) {
            $this->assertNull($this->messageFor($token), "$token ne doit rien recevoir");
        }

        // Une minute plus tard, le rappel du jour est déjà parti.
        $this->travel(1)->minutes();
        $this->assertSame([], app(ReminderService::class)->tick());
        Http::assertSentCount(5);
    }

    public function test_morning_question_comes_from_the_game(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 08:05'));
        $region = Region::query()->create(['name' => 'Savanes', 'slug' => 'savanes']);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Korhogo', 'slug' => 'korhogo']);
        $level = Level::query()->create(['city_id' => $city->id, 'title' => 'Niveau 1', 'slug' => 'niveau-1']);
        Question::query()->create(['level_id' => $level->id, 'question_text' => 'Quelle ville est la capitale des Sénoufo ?']);
        $this->playerWithDevice('regular', ['created_at' => now()->subDays(30), 'last_activity_date' => today()->subDay()]);

        app(ReminderService::class)->tick();

        $this->assertSame(
            ['title' => 'Question du matin ☀️', 'body' => 'Quelle ville est la capitale des Sénoufo ?'],
            $this->messageFor('regular')['notification'],
        );
    }

    public function test_nothing_leaves_during_quiet_hours(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 06:00'));
        PushAutomation::for('morning')->update(['settings' => ['hour' => 6]]);
        $this->playerWithDevice('regular', ['created_at' => now()->subDays(30), 'last_activity_date' => today()->subDay()]);

        $this->assertSame([], app(ReminderService::class)->tick());
        Http::assertNothingSent();
    }

    public function test_daily_cap_blocks_extra_reminders(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 08:00'));
        PushAutomation::query()->create(['key' => PushCatalog::GLOBAL_KEY, 'settings' => ['max_per_day' => 1, 'quiet_start' => 21, 'quiet_end' => 7]]);
        $player = $this->playerWithDevice('player', ['created_at' => now()->subDays(30), 'last_activity_date' => today()->subDay()]);
        UserLife::query()->create(['user_id' => $player->id, 'lives' => 0, 'max_lives' => 5, 'next_life_at' => now()->subHours(3)]);

        $this->assertSame(['morning' => 1, 'lives_full' => 0], array_intersect_key(app(ReminderService::class)->tick(), ['morning' => 0, 'lives_full' => 0]));
        $this->assertSame(1, $this->messagesFor('player'));
    }

    public function test_lives_full_is_sent_once_when_all_lives_are_back(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 10:00'));
        $player = $this->playerWithDevice('empty', ['name' => 'Awa Traoré']);
        // 0 vie : la 1re revient à 7 h 30, les 5 sont là à 9 h 30.
        UserLife::query()->create(['user_id' => $player->id, 'lives' => 0, 'max_lives' => 5, 'next_life_at' => now()->setTime(7, 30)]);
        $refilling = $this->playerWithDevice('refilling');
        UserLife::query()->create(['user_id' => $refilling->id, 'lives' => 0, 'max_lives' => 5, 'next_life_at' => now()->subMinutes(20)]);

        app(ReminderService::class)->tick();
        $this->assertSame(
            ['title' => 'Tes vies sont rechargées ⚡', 'body' => 'Awa, tes 5 vies t’attendent. On reprend où tu t’es arrêté ?'],
            $this->messageFor('empty')['notification'],
        );
        $this->assertNull($this->messageFor('refilling'));

        $this->travel(15)->minutes();
        app(ReminderService::class)->tick();
        $this->assertSame(1, $this->messagesFor('empty'));
    }

    public function test_streak_risk_warns_only_players_who_have_not_played_today(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 19:00'));
        foreach (['at-risk' => [12, 1], 'played' => [12, 0], 'short' => [1, 1]] as $token => [$streak, $daysAgo]) {
            $player = $this->playerWithDevice($token, ['last_activity_date' => today()->subDays($daysAgo)]);
            UserStreak::query()->create(['user_id' => $player->id, 'current_streak' => $streak, 'last_played_date' => today()->subDays($daysAgo)]);
        }

        app(ReminderService::class)->tick();

        $this->assertSame('Ta série est en danger 🔥', $this->messageFor('at-risk')['notification']['title']);
        $this->assertStringContainsString('12 jours', $this->messageFor('at-risk')['notification']['body']);
        $this->assertNull($this->messageFor('played'));
        $this->assertNull($this->messageFor('short'));
    }

    public function test_unanswered_challenge_is_reminded_once(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 11:00'));
        $challenger = $this->playerWithDevice('challenger', ['name' => 'Awa Traoré']);
        $opponent = $this->playerWithDevice('opponent');
        DB::table('friend_challenges')->insert([
            'code' => 'ABCD1234', 'challenger_id' => $challenger->id, 'opponent_id' => $opponent->id,
            'question_ids' => '[]', 'challenger_score' => 70, 'status' => 'in_progress',
            'created_at' => now()->subHours(25), 'updated_at' => now()->subHours(25),
        ]);

        app(ReminderService::class)->tick();
        $this->travel(2)->hours();
        app(ReminderService::class)->tick();

        $this->assertSame(1, $this->messagesFor('opponent'));
        $this->assertSame('Awa Traoré t’attend ⚔️', $this->messageFor('opponent')['notification']['title']);
        $this->assertSame(['type' => 'challenge', 'code' => 'ABCD1234'], $this->messageFor('opponent')['data']);
        $this->assertNull($this->messageFor('challenger'));
    }

    public function test_season_ending_and_unopened_chest_reminders(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 17:00'));
        $player = $this->playerWithDevice('player', ['name' => 'Koffi Kouassi', 'created_at' => now()->subDays(30), 'last_activity_date' => today()]);

        $leagueId = DB::table('leagues')->insertGetId(['name' => 'Ligue Or', 'slug' => 'or', 'rank_order' => 3, 'created_at' => now(), 'updated_at' => now()]);
        $seasonId = DB::table('league_seasons')->insertGetId(['league_id' => $leagueId, 'starts_at' => now()->subDays(6), 'ends_at' => now()->addHours(20), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('league_members')->insert(['league_season_id' => $seasonId, 'user_id' => $player->id, 'created_at' => now(), 'updated_at' => now()]);

        $chestId = DB::table('chests')->insertGetId(['name' => 'Coffre d’or', 'type' => 'gold', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_chests')->insert(['user_id' => $player->id, 'chest_id' => $chestId, 'status' => 'available', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);

        $this->assertSame(['chest_reminder' => 1, 'season_ending' => 1], array_filter(app(ReminderService::class)->tick()));

        $titles = collect(Http::recorded())->map(fn (array $pair) => $pair[0]['message']['notification']['title'])->all();
        $this->assertEqualsCanonicalizing(['Un coffre t’attend 🎁', 'Fin de saison dans 20 h 🏆'], $titles);
    }

    public function test_disabled_reminder_is_skipped_and_heartbeat_is_recorded(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 08:00'));
        PushAutomation::for('morning')->update(['enabled' => false]);
        $this->playerWithDevice('regular', ['created_at' => now()->subDays(30), 'last_activity_date' => today()->subDay()]);

        app(ReminderService::class)->tick();

        Http::assertNothingSent();
        $status = app(ReminderService::class)->status();
        $this->assertTrue($status['healthy']);
        $this->assertStringEndsWith('artisan schedule:run >> /dev/null 2>&1', $status['command']);
    }
}
