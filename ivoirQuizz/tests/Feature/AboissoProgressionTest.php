<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Models\Question;
use App\Models\User;
use App\Models\UserCityProgress;
use App\Models\UserLevelProgress;
use App\Models\UserRegionProgress;
use App\Services\Game\ProgressionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboissoProgressionTest extends TestCase
{
    use RefreshDatabase;

    private function recordCorrectKorhogoAnswers(User $user, int $count, int $duplicates = 0): void
    {
        $questions = Question::query()
            ->whereHas('level.city', fn ($query) => $query->where('slug', 'korhogo'))
            ->orderBy('source_key')
            ->limit($count)
            ->get();

        foreach ($questions as $question) {
            $city = $question->level->city;
            $correct = $question->answers()->where('is_correct', true)->firstOrFail();

            foreach (range(0, $question->id === $questions->first()->id ? $duplicates : 0) as $attempt) {
                $session = GameSession::query()->create([
                    'user_id' => $user->id,
                    'region_id' => $city->region_id,
                    'city_id' => $city->id,
                    'level_id' => $question->level_id,
                    'mode' => $question->level->is_boss ? 'boss' : 'level',
                    'status' => 'completed',
                    'total_questions' => 1,
                ]);
                GameSessionAnswer::query()->create([
                    'game_session_id' => $session->id,
                    'question_id' => $question->id,
                    'answer_id' => $correct->id,
                    'is_correct' => true,
                ]);
            }
        }
    }

    public function test_aboi­sso_unlocks_at_twenty_five_distinct_correct_questions_and_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $progression = app(ProgressionService::class);
        $city = City::query()->where('slug', 'aboisso')->firstOrFail();

        $this->recordCorrectKorhogoAnswers($user, 24, duplicates: 4);
        $this->assertFalse($progression->unlockAboissoIfEligible($user));
        $this->assertDatabaseMissing('user_city_progress', ['user_id' => $user->id, 'city_id' => $city->id, 'is_unlocked' => true]);

        $this->recordCorrectKorhogoAnswers($user, 25);
        $this->assertTrue($progression->unlockAboissoIfEligible($user));
        $this->assertTrue($progression->unlockAboissoIfEligible($user));

        $region = $city->region;
        $firstLevel = $city->levels()->orderBy('order')->firstOrFail();
        $this->assertSame(1, UserRegionProgress::query()->where('user_id', $user->id)->where('region_id', $region->id)->where('is_unlocked', true)->count());
        $this->assertDatabaseHas('user_city_progress', ['user_id' => $user->id, 'city_id' => $city->id, 'is_unlocked' => true]);
        $this->assertDatabaseHas('user_level_progress', ['user_id' => $user->id, 'level_id' => $firstLevel->id, 'is_unlocked' => true]);
        $this->assertSame(1, UserCityProgress::query()->where('user_id', $user->id)->where('city_id', $city->id)->count());
        $this->assertSame(1, UserLevelProgress::query()->where('user_id', $user->id)->where('level_id', $firstLevel->id)->count());
    }

    public function test_failed_and_mixed_sessions_do_not_count_toward_aboi­sso(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $question = Question::query()->whereHas('level.city', fn ($query) => $query->where('slug', 'korhogo'))->firstOrFail();
        $city = $question->level->city;
        $correct = $question->answers()->where('is_correct', true)->firstOrFail();

        foreach ([['failed', 'level'], ['completed', 'mixed']] as [$status, $mode]) {
            $session = GameSession::query()->create([
                'user_id' => $user->id,
                'region_id' => $city->region_id,
                'city_id' => $city->id,
                'level_id' => $question->level_id,
                'mode' => $mode,
                'status' => $status,
                'total_questions' => 1,
            ]);
            GameSessionAnswer::query()->create([
                'game_session_id' => $session->id,
                'question_id' => $question->id,
                'answer_id' => $correct->id,
                'is_correct' => true,
            ]);
        }

        $this->assertFalse(app(ProgressionService::class)->unlockAboissoIfEligible($user));
    }
}
