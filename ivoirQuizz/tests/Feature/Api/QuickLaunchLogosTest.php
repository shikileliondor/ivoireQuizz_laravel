<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Friendship;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuickLaunchLogosTest extends TestCase
{
    use RefreshDatabase;

    private Category $logos;

    private Category $history;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public', ['url' => 'http://example.test/storage']);

        $this->logos = Category::query()->create(['name' => 'Logos', 'slug' => Category::LOGOS]);
        $this->history = Category::query()->create(['name' => 'Histoire', 'slug' => 'histoire']);
    }

    public function test_logo_category_serves_its_questions_with_an_https_image_url(): void
    {
        $logo = $this->logoQuestion('questions/orange.png');
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/quiz/questions?mode=category&category_id='.$this->logos->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.questions')
            ->assertJsonPath('data.questions.0.id', $logo->id)
            ->assertJsonPath('data.questions.0.image_url', 'https://example.test/storage/questions/orange.png')
            ->assertJsonCount(2, 'data.questions.0.options')
            ->assertJsonMissingPath('data.questions.0.image_source');
    }

    public function test_mixed_and_daily_modes_leave_logos_out(): void
    {
        $this->logoQuestion('questions/orange.png');
        $history = $this->question($this->history);
        Sanctum::actingAs(User::factory()->create());

        foreach (['mixed', 'daily'] as $mode) {
            $this->getJson('/api/quiz/questions?mode='.$mode)
                ->assertOk()
                ->assertJsonCount(1, 'data.questions')
                ->assertJsonPath('data.questions.0.id', $history->id)
                ->assertJsonPath('data.questions.0.image_url', null);
        }
    }

    public function test_image_questions_without_their_file_are_not_served(): void
    {
        $this->question($this->logos, ['type' => 'image', 'image' => null]);
        $this->question($this->logos, ['type' => 'image', 'image' => 'questions/absent.png']);
        $playable = $this->logoQuestion('questions/mtn.png');
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/quiz/questions?mode=category&category_id='.$this->logos->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.questions')
            ->assertJsonPath('data.questions.0.id', $playable->id);
    }

    public function test_quick_launch_creates_a_session_and_returns_helper_configuration(): void
    {
        $question = $this->question($this->history, ['hint' => 'Un indice']);
        $user = User::factory()->create(['coins' => 200]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/quiz/questions?mode=chrono')
            ->assertOk()
            ->assertJsonPath('data.coins_balance', 200)
            ->assertJsonPath('data.seconds_per_question', 10)
            ->assertJsonPath('data.helpers.hint.available', true)
            ->assertJsonPath('data.helpers.hint.price', 30)
            ->assertJsonPath('data.helpers.skip.available', true)
            ->assertJsonPath('data.helpers.skip.price', 50)
            ->assertJsonPath('data.questions.0.id', $question->id);

        $session = GameSession::query()->findOrFail($response->json('data.session_id'));
        $this->assertSame($user->id, $session->user_id);
        $this->assertSame('chrono', $session->mode);
        $this->assertSame('started', $session->status);
        $this->assertSame([$question->id], $session->questions()->pluck('questions.id')->all());
    }

    public function test_challenge_without_category_leaves_logos_out(): void
    {
        $historyIds = collect(range(1, 10))->map(fn (): int => $this->question($this->history)->id)->sort()->values()->all();
        collect(range(1, 5))->each(fn (int $i) => $this->logoQuestion("questions/logo-{$i}.png"));
        [$awa, $koffi] = $this->friends();

        $questions = $this->actingAs($awa, 'sanctum')
            ->postJson('/api/challenges', ['opponent_id' => $koffi->id])
            ->assertCreated()
            ->json('data.questions');

        $this->assertSame($historyIds, collect($questions)->pluck('id')->sort()->values()->all());
    }

    public function test_challenge_on_the_logo_category_sends_image_urls(): void
    {
        collect(range(1, 10))->each(fn (int $i) => $this->logoQuestion("questions/logo-{$i}.png"));
        [$awa, $koffi] = $this->friends();

        $questions = $this->actingAs($awa, 'sanctum')
            ->postJson('/api/challenges', ['opponent_id' => $koffi->id, 'category_id' => $this->logos->id])
            ->assertCreated()
            ->json('data.questions');

        $this->assertCount(10, $questions);
        foreach ($questions as $question) {
            $this->assertStringStartsWith('https://example.test/storage/questions/logo-', $question['image_url']);
        }
    }

    private function logoQuestion(string $image): Question
    {
        Storage::disk('public')->put($image, 'png');

        return $this->question($this->logos, [
            'type' => 'image',
            'image' => $image,
            'image_source' => 'Site officiel, novembre 2026',
            'question_text' => 'Quelle entreprise utilise ce logo ?',
        ]);
    }

    private function question(Category $category, array $attributes = []): Question
    {
        $question = Question::query()->create([
            'category_id' => $category->id,
            'question_text' => 'Question ?',
            ...$attributes,
        ]);
        $question->answers()->create(['answer_text' => 'Bonne réponse', 'is_correct' => true, 'order' => 1]);
        $question->answers()->create(['answer_text' => 'Mauvaise réponse', 'is_correct' => false, 'order' => 2]);

        return $question;
    }

    /** @return array{User, User} */
    private function friends(): array
    {
        $awa = User::factory()->create();
        $koffi = User::factory()->create();
        Friendship::query()->create(['requester_id' => $awa->id, 'receiver_id' => $koffi->id, 'status' => 'accepted']);

        return [$awa, $koffi];
    }
}
