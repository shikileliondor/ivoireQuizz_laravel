<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Services\Admin\QuestionAdminService;
use Database\Seeders\LogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class LogosSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->dir = sys_get_temp_dir().'/logos-'.uniqid();
        File::makeDirectory($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_imports_logos_once_and_never_revives_archived_ones(): void
    {
        $this->png('Orange.png', 600);
        $this->png('BNI.png');
        $this->png('Ma_Marque.png');

        $this->runSeeder();
        $this->assertSame(3, Question::query()->where('is_active', true)->count());
        $this->assertDatabaseHas('categories', ['slug' => Category::LOGOS]);
        $this->assertSame('medium', $this->logo('BNI')->difficulty);
        $this->assertSame('easy', $this->logo('Ma Marque')->difficulty);
        $this->assertSame(2, $this->logo('Orange')->answers()->where('is_correct', false)->count());
        Storage::disk('public')->assertExists($this->logo('Orange')->image);
        $this->assertSame(600, getimagesize($this->dir.'/Orange.png')[0]);

        $this->runSeeder();
        $this->assertSame(3, Question::query()->count());

        app(QuestionAdminService::class)->archive($this->logo('BNI'));
        $this->runSeeder();
        $this->assertSame(2, Question::query()->count());
        $this->assertSame(3, Question::withTrashed()->count());
    }

    public function test_rejects_everything_when_one_file_is_too_small(): void
    {
        $this->png('Orange.png');
        $this->png('BNI.png', 50);

        try {
            $this->runSeeder();
            $this->fail('Le logo trop petit aurait dû être refusé.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('BNI.png', $exception->getMessage());
        }
        $this->assertSame(0, Question::query()->count());
    }

    public function test_empty_folder_does_nothing(): void
    {
        $this->runSeeder();
        $this->assertDatabaseMissing('categories', ['slug' => Category::LOGOS]);
    }

    private function runSeeder(): void
    {
        $seeder = new LogosSeeder;
        $seeder->directory = $this->dir;
        $seeder->setContainer($this->app)();
    }

    private function png(string $name, int $size = 200): void
    {
        $image = imagecreatetruecolor($size, $size);
        imagepng($image, $this->dir.'/'.$name);
    }

    private function logo(string $brand): Question
    {
        return Question::query()->whereHas('answers', fn ($q) => $q->where('is_correct', true)->where('answer_text', $brand))->firstOrFail();
    }
}
