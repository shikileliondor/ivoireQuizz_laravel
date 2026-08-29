<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Answer;
use App\Models\Chapter;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use App\Services\Game\GameCacheService;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function makeLevel(): Level
    {
        $region = Region::create(['name' => 'Abidjan', 'slug' => 'abidjan']);
        $chapter = Chapter::create(['region_id' => $region->id, 'name' => 'Plateau', 'slug' => 'plateau']);

        return Level::create(['chapter_id' => $chapter->id, 'title' => 'Niveau 1', 'slug' => 'niveau-1']);
    }

    public function test_a_region_is_created_with_a_generated_slug(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/admin/regions', ['name' => 'Yamoussoukro', 'order' => 2])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'yamoussoukro')
            ->assertJsonPath('data.name', 'Yamoussoukro');
    }

    public function test_duplicate_region_slugs_are_suffixed_instead_of_failing(): void
    {
        $this->actingAsAdmin();
        Region::create(['name' => 'Bouaké', 'slug' => 'bouake']);

        $this->postJson('/api/v1/admin/regions', ['name' => 'Bouaké'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'bouake-2');
    }

    public function test_writing_content_clears_the_cached_player_map(): void
    {
        $this->actingAsAdmin();
        Cache::put(GameCacheService::REGIONS_MAP_CACHE_KEY, ['stale'], 600);

        $this->postJson('/api/v1/admin/regions', ['name' => 'San Pedro'])->assertCreated();

        $this->assertNull(Cache::get(GameCacheService::REGIONS_MAP_CACHE_KEY));
    }

    public function test_a_question_is_created_with_its_answers_in_one_call(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();

        $response = $this->postJson('/api/v1/admin/questions', [
            'level_id' => $level->id,
            'question_text' => 'Quelle est la capitale politique de la Côte d’Ivoire ?',
            'explanation' => 'Yamoussoukro est capitale politique depuis 1983.',
            'difficulty' => 'easy',
            'answers' => [
                ['answer_text' => 'Yamoussoukro', 'is_correct' => true],
                ['answer_text' => 'Abidjan', 'is_correct' => false],
                ['answer_text' => 'Bouaké', 'is_correct' => false],
            ],
        ])->assertCreated();

        $questionId = $response->json('data.id');
        $this->assertCount(3, Answer::where('question_id', $questionId)->get());
        $this->assertSame('Yamoussoukro', Answer::where('question_id', $questionId)->where('is_correct', true)->value('answer_text'));
    }

    public function test_a_question_without_exactly_one_correct_answer_is_refused(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();

        $base = ['level_id' => $level->id, 'question_text' => 'Test ?'];

        $this->postJson('/api/v1/admin/questions', $base + ['answers' => [
            ['answer_text' => 'A', 'is_correct' => true],
            ['answer_text' => 'B', 'is_correct' => true],
        ]])->assertStatus(422)->assertJsonValidationErrors('answers');

        $this->postJson('/api/v1/admin/questions', $base + ['answers' => [
            ['answer_text' => 'A', 'is_correct' => false],
            ['answer_text' => 'B', 'is_correct' => false],
        ]])->assertStatus(422)->assertJsonValidationErrors('answers');

        $this->assertSame(0, Question::query()->count());
    }

    public function test_duplicate_answer_texts_are_refused(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();

        $this->postJson('/api/v1/admin/questions', [
            'level_id' => $level->id,
            'question_text' => 'Test ?',
            'answers' => [
                ['answer_text' => 'Abidjan', 'is_correct' => true],
                ['answer_text' => ' abidjan ', 'is_correct' => false],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('answers');
    }

    public function test_an_image_question_without_an_image_is_refused(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();

        $this->postJson('/api/v1/admin/questions', [
            'level_id' => $level->id,
            'question_text' => 'Quel monument ?',
            'type' => 'image',
            'answers' => [
                ['answer_text' => 'Basilique', 'is_correct' => true],
                ['answer_text' => 'Cathédrale', 'is_correct' => false],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_editing_answers_keeps_their_ids_so_history_survives(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();
        $question = Question::create(['level_id' => $level->id, 'question_text' => 'Avant ?', 'type' => 'text', 'difficulty' => 'easy']);
        $kept = Answer::create(['question_id' => $question->id, 'answer_text' => 'Bonne', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'Mauvaise', 'is_correct' => false, 'order' => 1]);

        $this->putJson("/api/v1/admin/questions/{$question->id}", [
            'level_id' => $level->id,
            'question_text' => 'Après ?',
            'answers' => [
                ['id' => $kept->id, 'answer_text' => 'Bonne corrigée', 'is_correct' => true],
                ['answer_text' => 'Autre', 'is_correct' => false],
            ],
        ])->assertOk()->assertJsonPath('data.question_text', 'Après ?');

        $this->assertSame('Bonne corrigée', $kept->fresh()->answer_text);
        $this->assertCount(2, $question->fresh()->answers);
    }

    public function test_a_batch_import_is_all_or_nothing(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();

        $this->postJson('/api/v1/admin/questions/import', [
            'level_id' => $level->id,
            'questions' => [
                ['question_text' => 'Bonne ligne ?', 'answers' => [
                    ['answer_text' => 'Oui', 'is_correct' => true],
                    ['answer_text' => 'Non', 'is_correct' => false],
                ]],
                ['question_text' => 'Ligne cassée ?', 'answers' => [
                    ['answer_text' => 'Oui', 'is_correct' => true],
                    ['answer_text' => 'Non', 'is_correct' => true],
                ]],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, Question::query()->count());
    }

    public function test_a_valid_batch_import_creates_every_row(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();

        $this->postJson('/api/v1/admin/questions/import', [
            'level_id' => $level->id,
            'questions' => [
                ['question_text' => 'Q1 ?', 'answers' => [['answer_text' => 'A', 'is_correct' => true], ['answer_text' => 'B', 'is_correct' => false]]],
                ['question_text' => 'Q2 ?', 'answers' => [['answer_text' => 'A', 'is_correct' => true], ['answer_text' => 'B', 'is_correct' => false]]],
            ],
        ])->assertCreated()->assertJsonPath('data.created', 2);

        $this->assertSame(2, Question::query()->count());
    }

    public function test_a_level_reports_whether_it_owns_enough_questions_to_run(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();
        $level->update(['questions_count' => 3]);

        Question::create(['level_id' => $level->id, 'question_text' => 'Q1 ?', 'type' => 'text', 'difficulty' => 'easy']);

        $this->getJson("/api/v1/admin/levels/{$level->id}")
            ->assertOk()
            ->assertJsonPath('data.available_questions', 1)
            ->assertJsonPath('data.is_playable', false)
            ->assertJsonPath('data.missing_questions', 2);
    }

    public function test_a_level_cannot_disagree_with_itself_about_being_a_boss(): void
    {
        $this->actingAsAdmin();
        $level = $this->makeLevel();

        $this->putJson("/api/v1/admin/levels/{$level->id}", ['node_type' => 'boss', 'is_boss' => false])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_boss');

        $this->putJson("/api/v1/admin/levels/{$level->id}", ['node_type' => 'level', 'is_boss' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors('node_type');
    }

    public function test_reordering_rewrites_the_sequence(): void
    {
        $this->actingAsAdmin();
        $a = Region::create(['name' => 'A', 'slug' => 'a', 'order' => 1]);
        $b = Region::create(['name' => 'B', 'slug' => 'b', 'order' => 2]);
        $c = Region::create(['name' => 'C', 'slug' => 'c', 'order' => 3]);

        $this->postJson('/api/v1/admin/regions/reorder', ['ids' => [$c->id, $a->id, $b->id]])
            ->assertOk()
            ->assertJsonPath('data.updated', 3);

        $this->assertSame(1, $c->fresh()->order);
        $this->assertSame(2, $a->fresh()->order);
        $this->assertSame(3, $b->fresh()->order);
    }

    /**
     * These two endpoints are the only raw-SQL aggregates in the back office,
     * so they are the most likely to break silently between SQLite and MySQL.
     */
    public function test_the_statistics_endpoints_answer_on_a_populated_database(): void
    {
        $admin = $this->actingAsAdmin();
        $level = $this->makeLevel();
        $question = Question::create(['level_id' => $level->id, 'question_text' => 'Q ?', 'type' => 'text', 'difficulty' => 'easy']);

        foreach (range(1, 6) as $i) {
            $session = GameSession::create([
                'user_id' => $admin->id,
                'level_id' => $level->id,
                'mode' => 'level',
                'status' => $i % 2 === 0 ? 'completed' : 'abandoned',
                'accuracy' => 60,
            ]);
            GameSessionAnswer::create([
                'game_session_id' => $session->id,
                'question_id' => $question->id,
                'is_correct' => $i % 2 === 0,
                'response_time' => 4,
            ]);
        }

        $this->getJson('/api/v1/admin/stats/question-balance?min_answers=1')
            ->assertOk()
            ->assertJsonStructure(['data' => ['hardest', 'easiest']]);

        $this->getJson('/api/v1/admin/stats/level-funnel')
            ->assertOk()
            ->assertJsonPath('data.0.attempts', 6)
            ->assertJsonPath('data.0.abandoned', 3);
    }

    public function test_question_stats_expose_the_success_rate(): void
    {
        $admin = $this->actingAsAdmin();
        $level = $this->makeLevel();
        $question = Question::create(['level_id' => $level->id, 'question_text' => 'Q ?', 'type' => 'text', 'difficulty' => 'easy']);

        // One row per session: a session may only answer a given question once.
        foreach ([true, true, false, false] as $correct) {
            $session = GameSession::create(['user_id' => $admin->id, 'level_id' => $level->id, 'mode' => 'level', 'status' => 'completed']);

            GameSessionAnswer::create([
                'game_session_id' => $session->id,
                'question_id' => $question->id,
                'is_correct' => $correct,
                'response_time' => 5,
            ]);
        }

        $this->getJson("/api/v1/admin/questions/{$question->id}")
            ->assertOk()
            ->assertJsonPath('data.stats.times_answered', 4)
            ->assertJsonPath('data.stats.success_rate', 50);
    }
}
