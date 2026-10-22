<?php

namespace Tests\Feature\Api\V1;

use App\Models\City;
use App\Models\Level;
use App\Models\Region;
use App\Models\User;
use App\Models\UserCityProgress;
use App\Models\UserLevelProgress;
use App\Models\UserRegionProgress;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GameMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_map_ok(): void
    {
        Sanctum::actingAs(User::factory()->create(['friend_code' => 'MAP001']));
        $this->getJson('/api/v1/game/map')->assertOk()->assertJsonPath('success', true);
    }

    public function test_map_shows_only_the_three_requested_regions_and_repairs_the_initial_unlock(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['friend_code' => 'MAP002']);
        $region = Region::query()->where('slug', 'abidjan')->firstOrFail();
        $city = City::query()->where('slug', 'yopougon')->firstOrFail();
        $level = Level::query()->where('city_id', $city->id)->orderBy('order')->firstOrFail();

        UserRegionProgress::query()->create(['user_id' => $user->id, 'region_id' => $region->id, 'is_unlocked' => false]);
        UserCityProgress::query()->create(['user_id' => $user->id, 'city_id' => $city->id, 'is_unlocked' => false]);
        UserLevelProgress::query()->create(['user_id' => $user->id, 'level_id' => $level->id, 'is_unlocked' => false]);
        Sanctum::actingAs($user);

        $payload = $this->getJson('/api/v1/game/map')->assertOk()->json('data');
        $this->assertSame(['abidjan', 'poro', 'sud-comoe'], array_column($payload['regions'], 'slug'));
        $this->assertTrue($payload['regions'][0]['is_unlocked']);
        $this->assertSame(['yopougon'], array_column($payload['regions'][0]['cities'], 'slug'));
        $this->assertTrue($payload['regions'][0]['cities'][0]['is_unlocked']);
        $this->assertTrue($payload['regions'][0]['cities'][0]['levels'][0]['is_unlocked']);
        $this->assertSame(['korhogo'], array_column($payload['regions'][1]['cities'], 'slug'));
        $this->assertSame(['aboisso'], array_column($payload['regions'][2]['cities'], 'slug'));
        $this->assertFalse($payload['regions'][1]['cities'][0]['is_unlocked']);
        $this->assertFalse($payload['regions'][2]['cities'][0]['is_unlocked']);
    }

    public function test_city_without_an_available_image_returns_null_without_breaking_the_map(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['friend_code' => 'MAP003']);
        $city = City::query()->where('slug', 'yopougon')->firstOrFail();
        $city->update(['image' => 'cities/missing-yopougon.jpg']);
        Storage::fake('public');
        Sanctum::actingAs($user);

        $payload = $this->getJson('/api/v1/game/map')
            ->assertOk()
            ->json('data');

        $this->assertSame('yopougon', $payload['regions'][0]['cities'][0]['slug']);
        $this->assertNull($payload['regions'][0]['cities'][0]['image_url']);
    }

    public function test_city_image_urls_are_absolute_https_and_included_in_level_details(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['friend_code' => 'MAP004']);
        $city = City::query()->where('slug', 'yopougon')->firstOrFail();
        $city->update(['image' => null]);
        Storage::fake('public');
        Storage::disk('public')->put('cities/yopougon.jpg', 'image-content');
        config(['filesystems.disks.public.url' => 'http://example.test/storage']);
        Storage::forgetDisk('public');
        $level = Level::query()->where('city_id', $city->id)->orderBy('order')->firstOrFail();
        Sanctum::actingAs($user);

        $mapPayload = $this->getJson('/api/v1/game/map')
            ->assertOk()
            ->json('data');
        $levelPayload = $this->getJson('/api/v1/levels/'.$level->id)
            ->assertOk()
            ->json('data');

        $this->assertSame('https://example.test/storage/cities/yopougon.jpg', $mapPayload['regions'][0]['cities'][0]['image_url']);
        $this->assertSame('https://example.test/storage/cities/yopougon.jpg', $levelPayload['city']['image_url']);
    }
}
