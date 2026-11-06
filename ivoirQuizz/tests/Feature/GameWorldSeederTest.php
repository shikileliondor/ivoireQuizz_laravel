<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\League;
use App\Models\Level;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameWorldSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_the_whole_world_once_without_overwriting_admin_changes(): void
    {
        Storage::fake('public');

        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        $this->assertDatabaseCount('leagues', 5);
        $this->assertDatabaseCount('regions', 7);
        $this->assertDatabaseCount('cities', 10);
        $this->assertDatabaseCount('chests', 3);
        $this->assertDatabaseCount('collectibles', 10);
        $this->assertSame(['aboisso', 'korhogo', 'yopougon'], City::query()->where('is_active', true)->orderBy('slug')->pluck('slug')->all());
        $this->assertSame(15, Level::query()->count());
        $this->assertSame(150, Question::query()->whereNotNull('level_id')->count());
        Storage::disk('public')->assertExists('cities/korhogo.jpg');
        $this->assertSame('cities/korhogo.jpg', City::query()->where('slug', 'korhogo')->value('image'));

        League::query()->where('slug', 'bronze')->update(['name' => 'Bronze renommée']);
        City::query()->where('slug', 'cocody')->update(['is_active' => true]);

        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        $this->assertDatabaseCount('leagues', 5);
        $this->assertSame(15, Level::query()->count());
        $this->assertSame(150, Question::query()->whereNotNull('level_id')->count());
        $this->assertDatabaseHas('leagues', ['slug' => 'bronze', 'name' => 'Bronze renommée']);
        $this->assertDatabaseHas('cities', ['slug' => 'cocody', 'is_active' => true]);
    }
}
