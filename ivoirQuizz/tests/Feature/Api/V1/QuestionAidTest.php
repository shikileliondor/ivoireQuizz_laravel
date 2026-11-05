<?php

namespace Tests\Feature\Api\V1;

use App\Models\City;
use App\Models\GameSession;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuestionAidTest extends TestCase
{
    private User $user;
    private GameSession $session;
    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['coins' => 100]);
        Sanctum::actingAs($this->user);
        $region = Region::query()->create(['name' => 'Lagunes', 'slug' => 'lagunes', 'order' => 1]);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Abidjan', 'slug' => 'abidjan', 'order' => 1]);
        $level = Level::query()->create(['city_id' => $city->id, 'title' => 'Niveau 1', 'slug' => 'niveau-1', 'order' => 1]);
        $this->question = Question::query()->create([
            'level_id' => $level->id, 'question_text' => 'Capitale ?', 'hint' => 'Ville économique',
            'difficulty' => 'easy', 'type' => 'text', 'points' => 10, 'xp_reward' => 5, 'time_limit' => 20, 'is_active' => true,
        ]);
        $this->session = GameSession::query()->create([
            'user_id' => $this->user->id, 'region_id' => $region->id, 'city_id' => $city->id, 'level_id' => $level->id,
            'mode' => 'chrono', 'status' => 'started', 'total_questions' => 10, 'seconds_per_question' => 10,
            'gameplay_config' => config('gameplay.defaults'), 'started_at' => now(),
        ]);
    }

    public function test_hint_is_debited_once_and_pauses_chrono(): void
    {
        $url = route('api.v1.game-sessions.hint', [$this->session, $this->question]);
        $this->postJson($url)->assertOk()
            ->assertJsonPath('data.cost', 30)->assertJsonPath('data.coins_balance', 70)
            ->assertJsonPath('data.hint', 'Ville économique')->assertJsonPath('success', true);
        $this->assertNotNull($this->postJson($url)->assertStatus(409));
        $this->assertSame(70, $this->user->refresh()->coins);
        $this->assertDatabaseCount('coin_transactions', 1);
    }

    public function test_skip_records_zero_point_answer_and_prevents_double_payment(): void
    {
        $url = route('api.v1.game-sessions.skip', [$this->session, $this->question]);
        $this->postJson($url)->assertOk()->assertJsonPath('data.cost', 50)->assertJsonPath('data.coins_balance', 50);
        $this->postJson($url)->assertStatus(409);
        $this->assertDatabaseHas('game_session_answers', ['game_session_id' => $this->session->id, 'question_id' => $this->question->id, 'status' => 'skipped', 'points_earned' => 0]);
        $this->assertSame(50, $this->user->refresh()->coins);
    }

    public function test_insufficient_balance_never_goes_negative(): void
    {
        $this->user->update(['coins' => 10]);
        $this->postJson(route('api.v1.game-sessions.hint', [$this->session, $this->question]))->assertStatus(422);
        $this->assertSame(10, $this->user->refresh()->coins);
        $this->assertDatabaseCount('game_question_actions', 0);
    }

    public function test_aid_rejects_question_outside_persisted_draw(): void
    {
        $this->session->questions()->attach($this->question->id, ['position' => 1]);
        $other = Question::query()->create([
            'level_id' => $this->question->level_id, 'question_text' => 'Autre ?', 'hint' => 'Secret',
            'difficulty' => 'easy', 'type' => 'text', 'points' => 10, 'xp_reward' => 5, 'time_limit' => 20, 'is_active' => true,
        ]);
        $this->postJson(route('api.v1.game-sessions.hint', [$this->session, $other]))->assertStatus(422);
        $this->assertSame(100, $this->user->refresh()->coins);
    }
}
