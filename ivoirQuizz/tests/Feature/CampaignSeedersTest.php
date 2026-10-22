<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Models\Level;
use App\Models\Question;
use App\Models\User;
use App\Models\UserLevelProgress;
use Database\Seeders\Campaigns\CampaignImporter;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Reference\CategoriesSeeder;
use Database\Seeders\Reference\CitiesSeeder;
use Database\Seeders\Reference\RegionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignSeedersTest extends TestCase
{
    use RefreshDatabase;

    private function importReferences(): void
    {
        (new CategoriesSeeder)->run();
        (new RegionsSeeder)->run();
        (new CitiesSeeder)->run();
    }

    private function korhogoConfig(string $file): array
    {
        return ['key' => 'korhogo', 'region_slug' => 'poro', 'city_slug' => 'korhogo', 'file' => $file];
    }

    public function test_database_seeder_is_repeatable_and_imports_campaigns(): void
    {
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertExitCode(0);
        $questionIds = Question::query()->orderBy('id')->pluck('id')->all();
        $this->assertCount(150, $questionIds);
        $this->assertDatabaseCount('levels', 15);
        $user = User::factory()->create();
        $level = Level::query()->firstOrFail();
        UserLevelProgress::query()->create([
            'user_id' => $user->id,
            'level_id' => $level->id,
            'best_score' => 321,
            'best_accuracy' => 80,
            'stars' => 2,
            'attempts' => 4,
            'is_unlocked' => true,
            'is_completed' => true,
        ]);

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertExitCode(0);
        $this->assertDatabaseCount('questions', 150);
        $this->assertSame($questionIds, Question::query()->orderBy('id')->pluck('id')->all());
        $expectedAnswers = 0;
        foreach (['korhogo', 'aboisso', 'yopougon'] as $city) {
            foreach (json_decode(file_get_contents(database_path("seeders/Campaigns/data/{$city}_questions.json")), true, 512, JSON_THROW_ON_ERROR) as $question) {
                $expectedAnswers += count($question['options']);
            }
        }
        $this->assertDatabaseCount('answers', $expectedAnswers);
        $this->assertDatabaseHas('user_level_progress', [
            'user_id' => $user->id,
            'level_id' => $level->id,
            'best_score' => 321,
            'best_accuracy' => 80,
            'stars' => 2,
            'attempts' => 4,
            'is_unlocked' => true,
            'is_completed' => true,
        ]);
    }

    public function test_changed_question_is_versioned_without_mutating_historical_question_or_answers(): void
    {
        $this->importReferences();
        $file = database_path('seeders/Campaigns/data/korhogo_questions.json');
        $bank = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        (new CampaignImporter)->import($this->korhogoConfig($file));

        $original = Question::query()->where('source_key', $bank[0]['source_key'])->firstOrFail();
        $originalAnswer = $original->answers()->orderBy('order')->firstOrFail();
        $user = User::factory()->create();
        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'region_id' => $original->level->city->region_id,
            'city_id' => $original->level->city_id,
            'level_id' => $original->level_id,
            'mode' => 'level',
            'status' => 'completed',
            'total_questions' => 1,
        ]);
        $history = GameSessionAnswer::query()->create([
            'game_session_id' => $session->id,
            'question_id' => $original->id,
            'answer_id' => $originalAnswer->id,
            'is_correct' => $originalAnswer->is_correct,
        ]);

        $bank[0]['question'] .= ' (version corrigée)';
        $bank[0]['options'][0]['text'] .= ' modifiée';
        $temporary = tempnam(sys_get_temp_dir(), 'ivoire-campaign-');
        file_put_contents($temporary, json_encode($bank, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        try {
            (new CampaignImporter)->import($this->korhogoConfig($temporary));
        } finally {
            unlink($temporary);
        }

        $current = Question::query()->where('source_key', $bank[0]['source_key'])->where('source_version', 2)->firstOrFail();
        $this->assertNotSame($original->id, $current->id);
        $this->assertSame(2, $current->source_version);
        $this->assertFalse($original->fresh()->is_active);
        $this->assertSame($history->id, GameSessionAnswer::findOrFail($history->id)->id);
        $this->assertSame($original->id, $history->fresh()->question_id);
        $this->assertSame($originalAnswer->id, $history->fresh()->answer_id);
        $this->assertSame($originalAnswer->answer_text, Answer::findOrFail($originalAnswer->id)->answer_text);
    }

    public function test_removed_question_is_deactivated_and_its_history_is_retained(): void
    {
        $this->importReferences();
        $file = database_path('seeders/Campaigns/data/korhogo_questions.json');
        $bank = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        (new CampaignImporter)->import($this->korhogoConfig($file));
        $removedData = $bank[49];
        $removed = Question::query()->where('source_key', $removedData['source_key'])->firstOrFail();
        $answer = $removed->answers()->firstOrFail();
        $user = User::factory()->create();
        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'region_id' => $removed->level->city->region_id,
            'city_id' => $removed->level->city_id,
            'level_id' => $removed->level_id,
            'mode' => 'boss',
            'status' => 'completed',
            'total_questions' => 1,
        ]);
        $history = GameSessionAnswer::query()->create([
            'game_session_id' => $session->id,
            'question_id' => $removed->id,
            'answer_id' => $answer->id,
            'is_correct' => false,
        ]);

        $bank[49]['source_key'] = 'korhogo.q.replacement050';
        $temporary = tempnam(sys_get_temp_dir(), 'ivoire-campaign-');
        file_put_contents($temporary, json_encode($bank, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        try {
            (new CampaignImporter)->import($this->korhogoConfig($temporary));
        } finally {
            unlink($temporary);
        }

        $this->assertFalse($removed->fresh()->is_active);
        $this->assertSame($removed->id, $history->fresh()->question_id);
        $this->assertSame($answer->id, $history->fresh()->answer_id);
        $this->assertSame($removedData['question'], $removed->fresh()->question_text);
        $this->assertDatabaseHas('questions', ['source_key' => 'korhogo.q.replacement050', 'is_active' => true]);
    }
}
