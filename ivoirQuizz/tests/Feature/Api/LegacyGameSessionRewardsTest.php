<?php

namespace Tests\Feature\Api;

use App\Models\Answer;
use App\Models\Category;
use App\Models\GameplaySetting;
use App\Models\Question;
use App\Models\User;
use App\Models\UserStreak;
use App\Services\Game\RewardService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyGameSessionRewardsTest extends TestCase
{
    public function test_chrono_session_credits_xp_earned_from_correct_answers(): void
    {
        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        [$correctQuestion, $correctAnswer] = $this->question($category, 70, 8);
        [$wrongQuestion, , $wrongAnswer] = $this->question($category, 100, 12);
        $user = User::factory()->create(['xp_total' => 20, 'total_score' => 30]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/sessions', [
            'mode' => 'chrono',
            'duration_seconds' => 18,
            'answers' => [
                ['question_id' => $correctQuestion->id, 'selected_option_id' => $correctAnswer->id, 'response_time_seconds' => 7],
                ['question_id' => $wrongQuestion->id, 'selected_option_id' => $wrongAnswer->id, 'response_time_seconds' => 11],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.score', 70)
            ->assertJsonPath('data.xp_earned', 8);

        $sessionId = $response->json('data.id');
        $this->assertDatabaseHas('game_sessions', ['id' => $sessionId, 'xp_earned' => 8]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'xp_total' => 28, 'total_score' => 100]);
        $this->assertDatabaseHas('reward_transactions', [
            'user_id' => $user->id,
            'type' => 'xp',
            'amount' => 8,
            'source_type' => 'game_session',
            'source_id' => $sessionId,
        ]);
    }

    public function test_every_quick_game_pays_its_answers_plus_win_and_perfect_bonuses_up_to_the_daily_limit(): void
    {
        $this->settings(['win_coins_daily_limit' => 3, 'streak_coins_per_day' => 0]);
        $user = User::factory()->create(['coins' => 0]);
        Sanctum::actingAs($user);
        $play = $this->quickGame('category');

        // 4/5 = 80 % : 4 bonnes réponses + 20 de victoire.
        $play(4)->assertJsonPath('data.coins_earned', 24)->assertJsonPath('data.rewards.coins', 24);
        // 3/5 = 60 % : perdue, mais les 3 bonnes réponses rapportent.
        $play(3)->assertJsonPath('data.coins_earned', 3);
        // 5/5 : 5 + 20 de victoire + 20 de sans-faute.
        $play(5)->assertJsonPath('data.coins_earned', 45);
        // Limite de 3 parties payées atteinte.
        $play(5)->assertJsonPath('data.coins_earned', 0);
        $this->assertSame(72, (int) $user->fresh()->coins);
    }

    public function test_daily_quiz_and_first_game_of_the_day_pay_their_bonus_once(): void
    {
        $this->settings(['coins_per_correct' => 0, 'perfect_game_coins' => 0, 'quick_win_coins' => 0]);
        $user = User::factory()->create(['coins' => 0]);
        UserStreak::query()->create(['user_id' => $user->id, 'current_streak' => 2, 'longest_streak' => 2, 'last_played_date' => now()->subDay()->toDateString()]);
        Sanctum::actingAs($user);
        $daily = $this->quickGame('daily');

        // Série de 3 jours : 30 ; quiz du jour : 30.
        $daily(5)->assertJsonPath('data.coins_earned', 60);
        $daily(5)->assertJsonPath('data.coins_earned', 0);
        $this->assertSame(60, (int) $user->fresh()->coins);
    }

    public function test_player_level_up_pays_coins(): void
    {
        $user = User::factory()->create(['coins' => 0, 'xp_total' => 0, 'current_level' => 1]);

        app(RewardService::class)->addXp($user, 500);

        $this->assertSame(2, (int) $user->fresh()->current_level);
        $this->assertSame(50, (int) $user->fresh()->coins);
    }

    /** @param  array<string, int>  $values */
    private function settings(array $values): void
    {
        foreach ($values as $key => $value) {
            GameplaySetting::query()->create(['key' => $key, 'value' => (string) $value]);
        }
    }

    /** Joue une partie de 5 questions avec le nombre de bonnes réponses voulu. */
    private function quickGame(string $mode): \Closure
    {
        $category = Category::query()->create(['name' => 'Histoire', 'slug' => 'histoire-'.$mode]);
        $questions = collect(range(1, 5))->map(fn (int $i): array => $this->question($category, 10 + $i, 0));

        return fn (int $correct) => $this->postJson('/api/sessions', [
            'mode' => $mode,
            'category_id' => $category->id,
            'duration_seconds' => 30,
            'answers' => $questions->map(fn (array $q, int $i): array => [
                'question_id' => $q[0]->id,
                'selected_option_id' => $i < $correct ? $q[1]->id : $q[2]->id,
                'response_time_seconds' => 5,
            ])->all(),
        ])->assertCreated();
    }

    /** @return array{Question, Answer, Answer} */
    private function question(Category $category, int $points, int $xp): array
    {
        $question = Question::query()->create([
            'category_id' => $category->id,
            'question_text' => "Question {$points}",
            'points' => $points,
            'xp_reward' => $xp,
            'is_active' => true,
        ]);
        $correct = $question->answers()->create(['answer_text' => 'Correct', 'is_correct' => true, 'order' => 1]);
        $incorrect = $question->answers()->create(['answer_text' => 'Incorrect', 'is_correct' => false, 'order' => 2]);

        return [$question, $correct, $incorrect];
    }
}
