<?php

namespace Tests\Feature\Api;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
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
