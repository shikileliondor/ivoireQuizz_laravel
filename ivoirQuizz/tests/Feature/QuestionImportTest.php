<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Models\Level;
use App\Models\Question;
use App\Models\User;
use App\Models\UserLevelProgress;
use App\Services\Questions\CampaignImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\GameWorld;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        GameWorld::categories();
        GameWorld::regions();
        GameWorld::cities();
    }

    protected function tearDown(): void
    {
        array_map('unlink', array_filter($this->files, 'is_file'));
        parent::tearDown();
    }

    private function writeBank(array $bank): string
    {
        $file = tempnam(sys_get_temp_dir(), 'ivoire-bank-');
        file_put_contents($file, json_encode($bank, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $this->files[] = $file;
    }

    private function importCommand(string $city, string $file, array $extra = []): int
    {
        return $this->artisan('questions:import', ['files' => [$file], '--city' => $city, '--region' => GameWorld::CAMPAIGNS[$city]] + $extra)->run();
    }

    public function test_command_imports_banks_and_reimporting_is_idempotent(): void
    {
        $files = array_map(fn (string $city): string => $this->writeBank(GameWorld::bank($city)), array_combine(array_keys(GameWorld::CAMPAIGNS), array_keys(GameWorld::CAMPAIGNS)));

        foreach ($files as $city => $file) {
            $this->assertSame(0, $this->importCommand($city, $file));
        }
        $questionIds = Question::query()->orderBy('id')->pluck('id')->all();
        $this->assertCount(150, $questionIds);
        $this->assertDatabaseCount('levels', 15);
        $this->assertDatabaseCount('answers', 600);

        $user = User::factory()->create();
        $level = Level::query()->firstOrFail();
        UserLevelProgress::query()->create([
            'user_id' => $user->id,
            'level_id' => $level->id,
            'best_score' => 321,
            'stars' => 2,
            'attempts' => 4,
            'is_unlocked' => true,
            'is_completed' => true,
        ]);

        foreach ($files as $city => $file) {
            $this->assertSame(0, $this->importCommand($city, $file));
        }
        $this->assertSame($questionIds, Question::query()->orderBy('id')->pluck('id')->all());
        $this->assertDatabaseCount('answers', 600);
        $this->assertDatabaseHas('user_level_progress', ['user_id' => $user->id, 'level_id' => $level->id, 'best_score' => 321, 'stars' => 2]);
    }

    public function test_dry_run_and_invalid_banks_write_nothing(): void
    {
        $this->assertSame(0, $this->importCommand('korhogo', $this->writeBank(GameWorld::bank('korhogo')), ['--dry-run' => true]));

        $bank = GameWorld::bank('korhogo');
        array_pop($bank);
        $this->assertSame(1, $this->importCommand('korhogo', $this->writeBank($bank)));
        $this->assertSame(1, $this->importCommand('korhogo', '/fichier/introuvable.json'));
        $this->artisan('questions:import', ['files' => [$this->writeBank(GameWorld::bank('korhogo'))], '--city' => 'korhogo'])->assertExitCode(1);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_quick_play_files_from_docs_import_once_without_city(): void
    {
        $files = glob(base_path('docs/questions/*_20_questions.json'));
        $this->assertCount(10, $files);

        $this->artisan('questions:import', ['files' => $files, '--dry-run' => true])->assertExitCode(0);
        $this->assertDatabaseCount('questions', 0);

        $this->artisan('questions:import', ['files' => $files])->assertExitCode(0);
        $this->assertSame(200, Question::query()->whereNull('level_id')->where('is_active', true)->count());
        $this->assertDatabaseCount('answers', 800);
        $question = Question::query()->where('question_text', 'En quelle année la Côte d’Ivoire a-t-elle proclamé son indépendance ?')->firstOrFail();
        $this->assertSame('histoire', $question->category->slug);
        $this->assertSame('1960', $question->answers()->where('is_correct', true)->value('answer_text'));
        $this->assertNotEmpty($question->hint);

        $this->artisan('questions:import', ['files' => $files])->assertExitCode(0);
        $this->assertDatabaseCount('questions', 200);

        $bank = json_decode(file_get_contents($files[0]), true, 512, JSON_THROW_ON_ERROR);
        $bank[0]['question'] = 'Nouvelle question ?';
        $bank[0]['options'][1]['correct'] = ! $bank[0]['options'][1]['correct'];
        $this->artisan('questions:import', ['files' => [$this->writeBank($bank)]])->assertExitCode(1);
        $this->assertDatabaseCount('questions', 200);
    }

    public function test_changed_question_is_versioned_without_mutating_historical_question_or_answers(): void
    {
        $bank = GameWorld::bank('korhogo');
        (new CampaignImporter)->importBank(GameWorld::campaign('korhogo'), $bank);

        $original = Question::query()->where('source_key', $bank[0]['source_key'])->firstOrFail();
        $originalAnswer = $original->answers()->orderBy('order')->firstOrFail();
        $history = $this->answerHistory($original, $originalAnswer, 'level');

        $bank[0]['question'] .= ' (version corrigée)';
        $bank[0]['options'][0]['text'] .= ' modifiée';
        (new CampaignImporter)->import(GameWorld::campaign('korhogo') + ['file' => $this->writeBank($bank)]);

        $current = Question::query()->where('source_key', $bank[0]['source_key'])->where('source_version', 2)->firstOrFail();
        $this->assertNotSame($original->id, $current->id);
        $this->assertFalse($original->fresh()->is_active);
        $this->assertSame($original->id, $history->fresh()->question_id);
        $this->assertSame($originalAnswer->id, $history->fresh()->answer_id);
        $this->assertSame($originalAnswer->answer_text, Answer::findOrFail($originalAnswer->id)->answer_text);
    }

    public function test_removed_question_is_deactivated_and_its_history_is_retained(): void
    {
        $bank = GameWorld::bank('korhogo');
        (new CampaignImporter)->importBank(GameWorld::campaign('korhogo'), $bank);
        $removedData = $bank[49];
        $removed = Question::query()->where('source_key', $removedData['source_key'])->firstOrFail();
        $answer = $removed->answers()->firstOrFail();
        $history = $this->answerHistory($removed, $answer, 'boss');

        $bank[49]['source_key'] = 'korhogo.q.replacement050';
        (new CampaignImporter)->importBank(GameWorld::campaign('korhogo'), $bank);

        $this->assertFalse($removed->fresh()->is_active);
        $this->assertSame($removed->id, $history->fresh()->question_id);
        $this->assertSame($answer->id, $history->fresh()->answer_id);
        $this->assertSame($removedData['question'], $removed->fresh()->question_text);
        $this->assertDatabaseHas('questions', ['source_key' => 'korhogo.q.replacement050', 'is_active' => true]);
    }

    private function answerHistory(Question $question, Answer $answer, string $mode): GameSessionAnswer
    {
        $session = GameSession::query()->create([
            'user_id' => User::factory()->create()->id,
            'region_id' => $question->level->city->region_id,
            'city_id' => $question->level->city_id,
            'level_id' => $question->level_id,
            'mode' => $mode,
            'status' => 'completed',
            'total_questions' => 1,
        ]);

        return GameSessionAnswer::query()->create([
            'game_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_id' => $answer->id,
            'is_correct' => $answer->is_correct,
        ]);
    }
}
