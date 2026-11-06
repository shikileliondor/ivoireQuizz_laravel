<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuickPlayExcludesCampaignsTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_play_and_category_counts_ignore_city_campaign_questions(): void
    {
        Storage::fake('public');
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        Sanctum::actingAs(User::factory()->create());

        $categories = collect($this->getJson('/api/categories?has_questions=1')->assertOk()->json('data'))->keyBy('slug');
        $this->assertEqualsCanonicalizing(
            ['agriculture', 'culture', 'economie', 'gastronomie', 'geographie', 'histoire', 'monuments', 'musique', 'personnalites', 'sport'],
            $categories->keys()->all(),
        );
        $this->assertSame([20], $categories->pluck('questions_count')->unique()->values()->all());

        $geographie = Category::query()->where('slug', 'geographie')->value('id');
        foreach (range(1, 5) as $ignored) {
            $ids = $this->getJson('/api/quiz/questions?mode=category&category_id='.$geographie)->assertOk()->json('data.questions.*.id');
            $this->assertSame(0, Question::query()->whereIn('id', $ids)->whereNotNull('level_id')->count());
        }
    }
}
