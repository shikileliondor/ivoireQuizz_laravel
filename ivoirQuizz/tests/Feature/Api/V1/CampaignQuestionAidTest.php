<?php

namespace Tests\Feature\Api\V1;

use App\Models\Answer;
use App\Models\City;
use App\Models\GameplaySetting;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use App\Models\UserLevelProgress;
use Database\Seeders\Reference\ChestsSeeder;
use Database\Seeders\Reference\LeaguesSeeder;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Indice et « passer » sur une session de Campagne (POST /v1/levels/{id}/start). */
class CampaignQuestionAidTest extends TestCase
{
    private User $user;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->seed([LeaguesSeeder::class, ChestsSeeder::class]);
        $region = Region::query()->create(['name' => 'Lagunes', 'slug' => 'lagunes', 'order' => 1, 'is_active' => true]);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Yopougon', 'slug' => 'yopougon', 'order' => 1, 'is_active' => true]);
        $this->level = Level::query()->create(['city_id' => $city->id, 'title' => 'Découverte', 'slug' => 'decouverte', 'order' => 1, 'passing_score' => 70, 'is_active' => true]);
        Level::query()->create(['city_id' => $city->id, 'title' => 'Explorateur', 'slug' => 'explorateur', 'order' => 2, 'is_active' => true]);
        foreach (range(1, 4) as $i) {
            $question = Question::query()->create(['level_id' => $this->level->id, 'question_text' => "Question {$i} ?", 'hint' => "Indice {$i}", 'points' => 10, 'time_limit' => 20, 'is_active' => true]);
            Answer::query()->create(['question_id' => $question->id, 'answer_text' => 'Bonne', 'is_correct' => true, 'order' => 1]);
            Answer::query()->create(['question_id' => $question->id, 'answer_text' => 'Mauvaise', 'is_correct' => false, 'order' => 2]);
        }
        $this->user = User::factory()->create(['coins' => 100]);
        Sanctum::actingAs($this->user);
    }

    public function test_start_returns_helpers_in_the_category_mode_format_with_the_same_prices(): void
    {
        GameplaySetting::query()->create(['key' => 'hint_cost', 'value' => '12']);

        $start = $this->start()
            ->assertJsonPath('data.coins_balance', 100)
            ->assertJsonPath('data.helpers.hint', ['available' => true, 'price' => 12])
            ->assertJsonPath('data.helpers.skip', ['available' => true, 'price' => 50])
            ->assertJsonPath('data.helpers.pause_on_hint', false)
            ->assertJsonPath('data.helpers.pause_seconds', 0)
            ->assertJsonPath('data.helpers.hint_cost', 12)
            ->assertJsonPath('data.seconds_per_question', null)
            ->assertJsonCount(4, 'data.questions')
            ->json('data');
        $this->assertIsInt($start['id']);

        $category = $this->getJson('/api/quiz/questions?mode=mixed')->assertOk()->json('data.helpers');
        $this->assertSame($category, Arr::only($start['helpers'], ['hint', 'skip', 'pause_on_hint', 'pause_seconds']));
    }

    public function test_chrono_campaign_start_announces_and_applies_the_hint_pause(): void
    {
        $start = $this->start('chrono')
            ->assertJsonPath('data.helpers.pause_on_hint', true)
            ->assertJsonPath('data.helpers.pause_seconds', 5)
            ->json('data');

        $this->hint($start['id'], $start['questions'][0]['id'])->assertOk()
            ->assertJsonPath('data.coins_balance', 70);
        $this->assertNotNull($this->hint($start['id'], $start['questions'][1]['id'])->json('data.timer_paused_until'));
    }

    public function test_hint_is_unavailable_when_no_question_of_the_draw_has_one(): void
    {
        Question::query()->where('level_id', $this->level->id)->update(['hint' => null]);

        $start = $this->start()
            ->assertJsonPath('data.helpers.hint.available', false)
            ->assertJsonPath('data.helpers.skip.available', true)
            ->json('data');

        $this->hint($start['id'], $start['questions'][0]['id'])->assertStatus(409);
        $this->assertSame(100, $this->user->refresh()->coins);
    }

    public function test_hint_on_a_campaign_session_debits_once_and_returns_the_balance(): void
    {
        $start = $this->start()->json('data');
        $questionId = $start['questions'][0]['id'];

        $this->hint($start['id'], $questionId)->assertOk()
            ->assertJsonPath('data.coins_balance', 70)
            ->assertJsonPath('data.hint', Question::query()->find($questionId)->hint)
            ->assertJsonPath('data.timer_paused_until', null);
        $this->hint($start['id'], $questionId)->assertStatus(409);

        $this->assertSame(70, $this->user->refresh()->coins);
        $this->assertDatabaseCount('coin_transactions', 1);
        $this->assertDatabaseCount('game_question_actions', 1);
    }

    public function test_insufficient_balance_returns_422_without_debit(): void
    {
        $this->user->update(['coins' => 10]);
        $start = $this->start()->assertJsonPath('data.coins_balance', 10)->json('data');
        $questionId = $start['questions'][0]['id'];

        $this->hint($start['id'], $questionId)->assertStatus(422);
        $this->postJson("/api/v1/game-sessions/{$start['id']}/questions/{$questionId}/skip")->assertStatus(422);

        $this->assertSame(10, $this->user->refresh()->coins);
        $this->assertDatabaseCount('game_question_actions', 0);
        $this->assertDatabaseCount('game_session_answers', 0);
    }

    public function test_skipped_question_scores_as_unanswered_and_finish_still_works(): void
    {
        $start = $this->start()->json('data');
        [$skipped, $rest] = [$start['questions'][0]['id'], array_slice($start['questions'], 1)];

        $this->postJson("/api/v1/game-sessions/{$start['id']}/questions/{$skipped}/skip")->assertOk()
            ->assertJsonPath('data.coins_balance', 50);
        $this->postJson("/api/v1/game-sessions/{$start['id']}/questions/{$skipped}/skip")->assertStatus(409);
        $this->answer($start['id'], $skipped)->assertStatus(422);
        foreach ($rest as $question) {
            $this->answer($start['id'], $question['id'])->assertOk()->assertJsonPath('data.is_correct', true);
        }

        $this->postJson("/api/v1/game-sessions/{$start['id']}/finish")->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.correct_answers', 3)
            ->assertJsonPath('data.score', 30)
            ->assertJsonPath('data.accuracy', '75.00');

        $this->assertDatabaseHas('game_session_answers', ['game_session_id' => $start['id'], 'question_id' => $skipped, 'status' => 'skipped', 'is_correct' => false, 'points_earned' => 0]);
        $this->assertTrue(UserLevelProgress::query()->where('user_id', $this->user->id)->where('level_id', $this->level->id)->value('is_completed'));
    }

    private function start(string $mode = 'level')
    {
        return $this->postJson("/api/v1/levels/{$this->level->id}/start", ['mode' => $mode])->assertCreated();
    }

    private function hint(int $sessionId, int $questionId)
    {
        return $this->postJson("/api/v1/game-sessions/{$sessionId}/questions/{$questionId}/hint");
    }

    private function answer(int $sessionId, int $questionId)
    {
        return $this->postJson("/api/v1/game-sessions/{$sessionId}/answer", [
            'question_id' => $questionId,
            'answer_id' => Answer::query()->where('question_id', $questionId)->where('is_correct', true)->value('id'),
            'response_time' => 3,
        ]);
    }
}
