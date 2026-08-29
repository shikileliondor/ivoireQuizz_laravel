<?php

namespace Tests\Feature\Api\V1;

use App\Enums\GameConstants;
use App\Exceptions\Game\NotEnoughLivesException;
use App\Models\Answer;
use App\Models\Chapter;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use App\Models\UserLevelProgress;
use App\Models\UserLife;
use App\Services\Game\AnswerQuestionService;
use App\Services\Game\FinishGameSessionService;
use App\Services\Game\GameSessionService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GameplayRulesTest extends TestCase
{
    private function makeLevel(array $overrides = []): Level
    {
        $region = Region::create(['name' => 'Abidjan', 'slug' => 'abidjan']);
        $chapter = Chapter::create(['region_id' => $region->id, 'name' => 'Plateau', 'slug' => 'plateau']);

        $level = Level::create(array_merge([
            'chapter_id' => $chapter->id,
            'title' => 'Niveau 1',
            'slug' => 'niveau-1',
            'questions_count' => 2,
            'passing_score' => 70,
            // Set explicitly: column defaults are not reflected back into the
            // in-memory model, and the start guard reads both.
            'node_type' => 'level',
            'is_active' => true,
        ], $overrides));

        foreach (range(1, 2) as $i) {
            $question = Question::create([
                'level_id' => $level->id,
                'question_text' => "Question $i ?",
                'type' => 'text',
                'difficulty' => 'easy',
                'points' => 10,
                'time_limit' => 20,
            ]);
            Answer::create(['question_id' => $question->id, 'answer_text' => 'Bonne', 'is_correct' => true, 'order' => 0]);
            Answer::create(['question_id' => $question->id, 'answer_text' => 'Mauvaise', 'is_correct' => false, 'order' => 1]);
        }

        return $level;
    }

    private function unlock(User $user, Level $level): void
    {
        UserLevelProgress::create(['user_id' => $user->id, 'level_id' => $level->id, 'is_unlocked' => true]);
    }

    public function test_a_normal_level_starts_even_with_zero_lives(): void
    {
        $user = User::factory()->create();
        $level = $this->makeLevel();
        $this->unlock($user, $level);
        UserLife::create(['user_id' => $user->id, 'lives' => 0, 'max_lives' => 5]);

        $session = app(GameSessionService::class)->start($user, $level);

        $this->assertSame(GameConstants::MODE_LEVEL, $session->mode);
    }

    public function test_a_boss_still_requires_a_life(): void
    {
        $user = User::factory()->create();
        $level = $this->makeLevel(['is_boss' => true, 'node_type' => 'boss']);
        $this->unlock($user, $level);
        UserLife::create(['user_id' => $user->id, 'lives' => 0, 'max_lives' => 5, 'next_life_at' => now()->addHour()]);

        $this->expectException(NotEnoughLivesException::class);
        app(GameSessionService::class)->start($user, $level);
    }

    public function test_an_untimed_level_accepts_a_slow_answer_and_pays_full_points(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $level = $this->makeLevel();
        $this->unlock($user, $level);

        $session = app(GameSessionService::class)->start($user, $level);
        $question = $session->questions()->with('answers')->first();
        $correctId = $question->answers->firstWhere('is_correct', true)->id;

        // 90s on a question whose time_limit is 20s: refused before, fine now.
        $this->postJson("/api/v1/game-sessions/{$session->id}/answer", [
            'question_id' => $question->id,
            'answer_id' => $correctId,
            'response_time' => 90,
        ])
            ->assertOk()
            ->assertJsonPath('data.is_correct', true)
            ->assertJsonPath('data.points_earned', 10)
            ->assertJsonPath('data.explanation', $question->explanation);
    }

    public function test_a_boss_still_refuses_an_answer_past_the_time_limit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $level = $this->makeLevel(['is_boss' => true, 'node_type' => 'boss']);
        $this->unlock($user, $level);

        $session = app(GameSessionService::class)->start($user, $level);
        $question = $session->questions()->with('answers')->first();

        $this->postJson("/api/v1/game-sessions/{$session->id}/answer", [
            'question_id' => $question->id,
            'answer_id' => $question->answers->firstWhere('is_correct', true)->id,
            'response_time' => 90,
        ])->assertStatus(422);
    }

    public function test_a_timed_boss_still_rewards_speed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $level = $this->makeLevel(['is_boss' => true, 'node_type' => 'boss']);
        $this->unlock($user, $level);

        $session = app(GameSessionService::class)->start($user, $level);
        $question = $session->questions()->with('answers')->first();

        $this->postJson("/api/v1/game-sessions/{$session->id}/answer", [
            'question_id' => $question->id,
            'answer_id' => $question->answers->firstWhere('is_correct', true)->id,
            'response_time' => 0,
        ])
            ->assertOk()
            // 10 base points x 1.0 easy x (1 + full time ratio) = 20
            ->assertJsonPath('data.points_earned', 20);
    }

    public function test_failing_a_normal_level_costs_no_life(): void
    {
        $user = User::factory()->create();
        $level = $this->makeLevel();
        $this->unlock($user, $level);
        UserLife::create(['user_id' => $user->id, 'lives' => 5, 'max_lives' => 5]);

        $this->playSession($user, $level, correct: false);

        $this->assertSame(5, $user->userLives()->first()->lives);
    }

    public function test_failing_a_boss_costs_a_life(): void
    {
        $user = User::factory()->create();
        $level = $this->makeLevel(['is_boss' => true, 'node_type' => 'boss']);
        $this->unlock($user, $level);
        UserLife::create(['user_id' => $user->id, 'lives' => 5, 'max_lives' => 5]);

        $this->playSession($user, $level, correct: false);

        $this->assertSame(4, $user->userLives()->first()->lives);
    }

    public function test_replaying_a_cleared_level_gives_a_life_back(): void
    {
        $user = User::factory()->create();
        $level = $this->makeLevel();
        $this->unlock($user, $level);
        UserLife::create(['user_id' => $user->id, 'lives' => 2, 'max_lives' => 5]);

        $this->playSession($user, $level, correct: true);
        $this->assertSame(2, $user->userLives()->first()->lives, 'A first clear is not a revision.');

        $this->playSession($user, $level, correct: true);
        $this->assertSame(3, $user->userLives()->first()->lives, 'Replaying a cleared level heals one life.');
    }

    public function test_the_life_reward_never_exceeds_the_maximum(): void
    {
        $user = User::factory()->create();
        $level = $this->makeLevel();
        $this->unlock($user, $level);
        UserLife::create(['user_id' => $user->id, 'lives' => 5, 'max_lives' => 5]);

        $this->playSession($user, $level, correct: true);
        $this->playSession($user, $level, correct: true);

        $this->assertSame(5, $user->userLives()->first()->lives);
    }

    private function playSession(User $user, Level $level, bool $correct): void
    {
        $session = app(GameSessionService::class)->start($user, $level);
        $answerService = app(AnswerQuestionService::class);

        foreach ($session->questions()->with('answers')->get() as $question) {
            $answer = $question->answers->firstWhere('is_correct', $correct);
            $answerService->submitAnswer($session, $question, $answer, 5);
        }

        app(FinishGameSessionService::class)->finish($session);
    }
}
