<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Chest;
use App\Models\City;
use App\Models\Collectible;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Models\League;
use App\Models\LeagueSeason;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Region;
use App\Models\RewardTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Affiche chaque écran du back-office avec les vraies données de campagne pour détecter les erreurs de vue.
 */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_page_renders_with_seeded_data(): void
    {
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);

        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Awa Admin']);
        $player = User::factory()->create(['last_activity_date' => today(), 'xp_total' => 420, 'coins' => 30]);
        $question = Question::query()->with('answers')->firstOrFail();
        $level = $question->level;

        $session = GameSession::query()->create([
            'user_id' => $player->id,
            'region_id' => $level->city->region_id,
            'city_id' => $level->city_id,
            'level_id' => $level->id,
            'mode' => 'level',
            'status' => 'completed',
            'score' => 80,
            'correct_answers' => 1,
            'total_questions' => 1,
            'accuracy' => 100,
            'duration_seconds' => 95,
            'started_at' => now()->subMinutes(2),
            'finished_at' => now(),
        ]);
        GameSessionAnswer::query()->create([
            'game_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_id' => $question->answers->firstWhere('is_correct', true)->id,
            'is_correct' => true,
            'response_time' => 4,
            'points_earned' => 10,
        ]);
        $report = QuestionReport::query()->create([
            'user_id' => $player->id,
            'question_id' => $question->id,
            'reason' => 'typo',
            'message' => 'Il manque un accent.',
        ]);
        RewardTransaction::query()->create(['user_id' => $player->id, 'type' => 'coin', 'amount' => 30, 'source_type' => 'admin', 'description' => 'Bonus']);
        $league = League::query()->firstOrFail();
        LeagueSeason::query()->create(['league_id' => $league->id, 'starts_at' => now()->startOfWeek(), 'ends_at' => now()->endOfWeek(), 'status' => 'active']);

        $this->actingAs($admin);

        $pages = [
            route('admin.dashboard'),
            route('admin.users.index'),
            route('admin.users.index', ['q' => $player->email, 'status' => 'active', 'sort' => 'xp']),
            route('admin.users.show', $player),
            route('admin.users.show', $admin),
            route('admin.users.edit', $player),
            route('admin.sessions.index'),
            route('admin.sessions.index', ['user' => $player->id, 'status' => 'completed', 'from' => today()->toDateString()]),
            route('admin.sessions.show', $session),
            route('admin.regions.index'),
            route('admin.regions.create'),
            route('admin.regions.edit', Region::query()->firstOrFail()),
            route('admin.cities.index'),
            route('admin.cities.create'),
            route('admin.cities.edit', City::query()->firstOrFail()),
            route('admin.levels.index'),
            route('admin.levels.index', ['city' => $level->city_id, 'difficulty' => 'easy']),
            route('admin.levels.create', ['city' => $level->city_id]),
            route('admin.levels.edit', Level::query()->firstOrFail()),
            route('admin.categories.index'),
            route('admin.categories.create'),
            route('admin.categories.edit', $question->category ?? Category::query()->firstOrFail()),
            route('admin.questions.index'),
            route('admin.questions.index', ['level' => $level->id, 'source' => 'campaign', 'status' => 'active', 'q' => 'a']),
            route('admin.questions.create', ['level' => $level->id]),
            route('admin.questions.edit', $question),
            route('admin.reports.index'),
            route('admin.reports.index', ['status' => 'all']),
            route('admin.reports.show', $report),
            route('admin.chests.index'),
            route('admin.chests.create'),
            route('admin.chests.edit', Chest::query()->firstOrFail()),
            route('admin.collectibles.index'),
            route('admin.collectibles.create'),
            route('admin.collectibles.edit', Collectible::query()->firstOrFail()),
            route('admin.leagues.index'),
            route('admin.leagues.create'),
            route('admin.leagues.edit', $league),
        ];

        foreach ($pages as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('admin.dashboard'))
            ->assertSee('Parties par jour')
            ->assertSee($question->question_text);
        $this->get(route('admin.reports.show', $report))->assertSee('Il manque un accent.');
        $this->get(route('admin.questions.edit', $question))->assertSee($question->source_key);
    }
}
