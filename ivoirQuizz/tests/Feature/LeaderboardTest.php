<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Models\Level;
use App\Models\Question;
use App\Models\User;
use App\Models\UserLevelProgress;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_leaderboard_uses_best_campaign_scores_and_reports_personal_rank(): void
    {
        $this->seed(DatabaseSeeder::class);
        $leader = User::factory()->create(['total_score' => 50]);
        $currentUser = User::factory()->create(['total_score' => 50000]);
        $levels = Level::query()->whereHas('city', fn ($query) => $query->where('slug', 'korhogo'))->orderBy('order')->take(2)->get();

        UserLevelProgress::query()->create([
            'user_id' => $leader->id,
            'level_id' => $levels[0]->id,
            'best_score' => 100,
            'is_completed' => true,
        ]);
        UserLevelProgress::query()->create([
            'user_id' => $leader->id,
            'level_id' => $levels[1]->id,
            'best_score' => 150,
            'is_completed' => true,
        ]);
        UserLevelProgress::query()->create([
            'user_id' => $currentUser->id,
            'level_id' => $levels[0]->id,
            'best_score' => 200,
            'is_completed' => true,
        ]);

        Sanctum::actingAs($currentUser);
        $payload = $this->getJson('/api/leaderboard/global')->assertOk()->json('data');

        $this->assertSame($leader->id, $payload['leaderboard']['data'][0]['id']);
        $this->assertSame(250, (int) $payload['leaderboard']['data'][0]['score']);
        $this->assertSame(2, $payload['current_user_rank']);
        $this->assertSame(200, $payload['current_score']);
    }

    public function test_weekly_leaderboard_counts_each_stable_question_once_and_resets_by_week(): void
    {
        CarbonImmutable::setTestNow('2026-10-22 12:00:00');
        try {
            $this->seed(DatabaseSeeder::class);
            $leader = User::factory()->create();
            $currentUser = User::factory()->create();
            $city = City::query()->where('slug', 'yopougon')->firstOrFail();
            $questions = Question::query()->whereHas('level', fn ($query) => $query->where('city_id', $city->id))->orderBy('id')->take(2)->get();
            $firstQuestion = $questions[0];
            $secondQuestion = $questions[1];
            $version = Question::query()->create([
                'level_id' => $firstQuestion->level_id,
                'category_id' => $firstQuestion->category_id,
                'source_key' => $firstQuestion->source_key,
                'source_version' => 2,
                'content_hash' => str_repeat('a', 64),
                'question_text' => $firstQuestion->question_text.' version 2',
                'type' => 'text',
                'difficulty' => $firstQuestion->difficulty,
                'points' => $firstQuestion->points,
                'is_active' => true,
            ]);

            $this->recordAnswer($leader, $firstQuestion, 10, '2026-10-20 12:00:00');
            $this->recordAnswer($leader, $version, 30, '2026-10-21 12:00:00');
            $this->recordAnswer($leader, $secondQuestion, 15, '2026-10-22 11:00:00');
            $this->recordAnswer($leader, $secondQuestion, 100, '2026-10-18 12:00:00');
            $this->recordAnswer($currentUser, $firstQuestion, 20, '2026-10-22 10:00:00');

            Sanctum::actingAs($currentUser);
            $payload = $this->getJson('/api/leaderboard/weekly')->assertOk()->json('data');

            $this->assertSame('2026-10-19', $payload['week_start']);
            $this->assertSame('2026-10-25', $payload['week_end']);
            $this->assertSame($leader->id, $payload['leaderboard']['data'][0]['id']);
            $this->assertSame(45, (int) $payload['leaderboard']['data'][0]['score']);
            $this->assertSame(2, $payload['current_user_rank']);
            $this->assertSame(20, $payload['weekly_points']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function recordAnswer(User $user, Question $question, int $points, string $finishedAt): void
    {
        $city = $question->level->city;
        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'region_id' => $city->region_id,
            'city_id' => $city->id,
            'level_id' => $question->level_id,
            'mode' => 'level',
            'status' => 'completed',
            'total_questions' => 1,
            'finished_at' => $finishedAt,
        ]);
        GameSessionAnswer::query()->create([
            'game_session_id' => $session->id,
            'question_id' => $question->id,
            'points_earned' => $points,
            'is_correct' => true,
            'response_time' => 8,
        ]);
    }
}
