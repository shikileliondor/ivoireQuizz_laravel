<?php

namespace Tests\Feature\Api\V1;

use App\Models\Answer;
use App\Models\City;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\GameWorld;
use Tests\TestCase;

/**
 * Parcours réel de la Campagne par l'API : start -> answer -> finish, puis lecture de GET /v1/game/map.
 */
class CampaignUnlockChainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Un parcours complet dépasse les quotas par minute (start 10, answer 30) : ils ne sont pas l'objet du test.
        $this->withoutMiddleware(ThrottleRequests::class);    }

    public function test_finishing_yopougon_unlocks_korhogo_then_finishing_korhogo_unlocks_aboisso(): void
    {
        GameWorld::build();
        Sanctum::actingAs(User::factory()->create());

        $map = $this->map();
        $this->assertFalse($this->city($map, 'poro', 'korhogo')['is_unlocked']);
        $korhogoFirst = $this->firstLevel('korhogo');
        $this->postJson("/api/v1/levels/{$korhogoFirst->id}/start")->assertStatus(422);

        $this->playCity('yopougon');

        $map = $this->map();
        $abidjan = $this->region($map, 'abidjan');
        $yopougon = $this->city($map, 'abidjan', 'yopougon');
        $this->assertTrue($abidjan['is_completed']);
        $this->assertSame(100.0, (float) $abidjan['progress_percent']);
        $this->assertTrue($yopougon['is_completed']);
        $this->assertSame(100.0, (float) $yopougon['progress_percent']);
        $this->assertSame([true], array_values(array_unique(array_column($yopougon['levels'], 'is_completed'))));

        $this->assertTrue($this->region($map, 'poro')['is_unlocked']);
        $this->assertFalse($this->region($map, 'poro')['is_completed']);
        $korhogo = $this->city($map, 'poro', 'korhogo');
        $this->assertTrue($korhogo['is_unlocked']);
        $this->assertFalse($korhogo['is_completed']);
        $this->assertTrue($korhogo['levels'][0]['is_unlocked']);
        $this->assertFalse($korhogo['levels'][1]['is_unlocked']);
        $this->assertFalse($this->region($map, 'sud-comoe')['is_unlocked']);

        $this->playCity('korhogo');

        $map = $this->map();
        $this->assertTrue($this->region($map, 'poro')['is_completed']);
        $this->assertTrue($this->city($map, 'poro', 'korhogo')['is_completed']);
        $this->assertTrue($this->region($map, 'sud-comoe')['is_unlocked']);
        $aboisso = $this->city($map, 'sud-comoe', 'aboisso');
        $this->assertTrue($aboisso['is_unlocked']);
        $this->assertFalse($aboisso['is_completed']);
        $this->assertTrue($aboisso['levels'][0]['is_unlocked']);
    }

    public function test_finishing_a_city_unlocks_the_next_city_then_the_next_region(): void
    {
        GameWorld::leagues();
        GameWorld::chests();
        $first = $this->makeRegion('premiere', 1);
        $second = $this->makeRegion('deuxieme', 2);
        $cityA = $this->makeCity($first, 'ville-a', 1);
        $cityB = $this->makeCity($first, 'ville-b', 2);
        $cityC = $this->makeCity($second, 'ville-c', 1);
        $this->makeLevel($cityA, 1);
        $this->makeLevel($cityA, 2);
        $this->makeLevel($cityB, 1);
        $this->makeLevel($cityB, 2, boss: true);
        $this->makeLevel($cityC, 1);
        $this->makeLevel($cityC, 2);
        Sanctum::actingAs(User::factory()->create());

        $this->playCity('ville-a');

        $map = $this->map();
        $this->assertTrue($this->city($map, 'premiere', 'ville-a')['is_completed']);
        $this->assertTrue($this->city($map, 'premiere', 'ville-b')['is_unlocked']);
        $this->assertTrue($this->city($map, 'premiere', 'ville-b')['levels'][0]['is_unlocked']);
        $this->assertFalse($this->region($map, 'premiere')['is_completed']);
        $this->assertSame(50.0, (float) $this->region($map, 'premiere')['progress_percent']);
        $this->assertFalse($this->region($map, 'deuxieme')['is_unlocked']);

        $this->playCity('ville-b');

        $map = $this->map();
        $this->assertTrue($this->city($map, 'premiere', 'ville-b')['is_completed']);
        $this->assertTrue($this->region($map, 'premiere')['is_completed']);
        $this->assertTrue($this->region($map, 'deuxieme')['is_unlocked']);
        $this->assertTrue($this->city($map, 'deuxieme', 'ville-c')['is_unlocked']);
        $this->assertTrue($this->city($map, 'deuxieme', 'ville-c')['levels'][0]['is_unlocked']);
        $this->assertFalse($this->city($map, 'deuxieme', 'ville-c')['levels'][1]['is_unlocked']);
    }

    private function playCity(string $citySlug): void
    {
        $city = City::query()->where('slug', $citySlug)->where('is_active', true)->firstOrFail();
        foreach ($city->levels()->where('is_active', true)->orderBy('order')->orderBy('id')->get() as $level) {
            $this->playLevel($level);
        }
    }

    private function playLevel(Level $level): void
    {
        $session = $this->postJson("/api/v1/levels/{$level->id}/start")->assertCreated()->json('data');
        $this->assertNotEmpty($session['questions'], "Le niveau {$level->slug} n'a aucune question.");

        foreach ($session['questions'] as $question) {
            $this->postJson("/api/v1/game-sessions/{$session['id']}/answer", [
                'question_id' => $question['id'],
                'answer_id' => Answer::query()->where('question_id', $question['id'])->where('is_correct', true)->value('id'),
                'response_time' => 3,
            ])->assertOk()->assertJsonPath('data.is_correct', true);
        }

        $this->postJson("/api/v1/game-sessions/{$session['id']}/finish")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    private function map(): array
    {
        return $this->getJson('/api/v1/game/map')->assertOk()->json('data');
    }

    private function region(array $map, string $slug): array
    {
        $region = collect($map['regions'])->firstWhere('slug', $slug);
        $this->assertNotNull($region, "Région {$slug} absente de la carte.");

        return $region;
    }

    private function city(array $map, string $regionSlug, string $citySlug): array
    {
        $city = collect($this->region($map, $regionSlug)['cities'])->firstWhere('slug', $citySlug);
        $this->assertNotNull($city, "Ville {$citySlug} absente de la carte.");

        return $city;
    }

    private function firstLevel(string $citySlug): Level
    {
        return City::query()->where('slug', $citySlug)->firstOrFail()
            ->levels()->where('is_active', true)->orderBy('order')->firstOrFail();
    }

    private function makeRegion(string $slug, int $order): Region
    {
        return Region::query()->create(['name' => ucfirst($slug), 'slug' => $slug, 'order' => $order, 'is_active' => true]);
    }

    private function makeCity(Region $region, string $slug, int $order): City
    {
        return City::query()->create(['region_id' => $region->id, 'name' => ucfirst($slug), 'slug' => $slug, 'order' => $order, 'is_active' => true]);
    }

    private function makeLevel(City $city, int $order, bool $boss = false, bool $active = true, int $questions = 3): Level
    {
        $level = Level::query()->create([
            'city_id' => $city->id,
            'title' => "Niveau {$order}",
            'slug' => "{$city->slug}-niveau-{$order}".($active ? '' : '-off'),
            'order' => $order,
            'passing_score' => 70,
            'is_boss' => $boss,
            'is_active' => $active,
        ]);

        for ($i = 1; $i <= $questions; $i++) {
            $question = Question::query()->create(['level_id' => $level->id, 'question_text' => "{$level->slug} question {$i}", 'is_active' => true]);
            Answer::query()->create(['question_id' => $question->id, 'answer_text' => 'Bonne', 'is_correct' => true, 'order' => 1]);
            Answer::query()->create(['question_id' => $question->id, 'answer_text' => 'Mauvaise', 'is_correct' => false, 'order' => 2]);
        }

        return $level;
    }
}
