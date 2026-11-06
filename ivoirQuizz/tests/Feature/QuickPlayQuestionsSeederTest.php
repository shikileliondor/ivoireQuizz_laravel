<?php

namespace Tests\Feature;

use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickPlayQuestionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_categories_and_imports_questions_once(): void
    {
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        $this->assertSame(200, Question::query()->whereNull('level_id')->where('is_active', true)->count());
        $this->assertDatabaseHas('categories', ['slug' => 'geographie', 'name' => 'Géographie', 'is_active' => true]);

        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        $this->assertDatabaseCount('questions', 200);
    }
}
