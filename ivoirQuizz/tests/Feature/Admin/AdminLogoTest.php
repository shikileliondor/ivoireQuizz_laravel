<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\City;
use App\Models\GameSession;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use App\Services\Game\GameSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminLogoTest extends TestCase
{
    use RefreshDatabase;

    private Level $level;

    private Category $logos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');

        $region = Region::query()->create(['name' => 'Lagunes', 'slug' => 'lagunes']);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Abidjan', 'slug' => 'abidjan']);
        $this->level = Level::query()->create(['city_id' => $city->id, 'title' => 'Niveau 1', 'slug' => 'niveau-1']);
        $this->logos = Category::query()->create(['name' => 'Logos', 'slug' => Category::LOGOS]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_logo_question_cannot_have_a_level_and_must_be_an_image(): void
    {
        $this->post(route('admin.questions.store'), $this->payload([
            'level_id' => $this->level->id,
            'type' => 'text',
        ]))->assertSessionHasErrors(['level_id', 'type']);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_image_question_about_a_logo_must_be_in_the_logo_category(): void
    {
        $other = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);

        $this->post(route('admin.questions.store'), $this->payload([
            'category_id' => $other->id,
            'level_id' => $this->level->id,
        ]))->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_logo_must_be_a_png_or_webp_large_enough(): void
    {
        $this->post(route('admin.questions.store'), $this->payload([
            'image' => UploadedFile::fake()->image('logo.jpg', 256, 256),
        ]))->assertSessionHasErrors('image');

        $this->post(route('admin.questions.store'), $this->payload([
            'image' => UploadedFile::fake()->image('logo.png', 64, 64),
        ]))->assertSessionHasErrors('image');

        // Un logo horizontal peut être peu haut : seul le plus grand côté compte.
        $this->post(route('admin.questions.store'), $this->payload([
            'image' => UploadedFile::fake()->image('logo.png', 300, 80),
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('questions', 1);
    }

    public function test_large_logo_is_shrunk_on_upload(): void
    {
        $this->post(route('admin.questions.store'), $this->payload([
            'image' => UploadedFile::fake()->image('logo.png', 1200, 600),
        ]))->assertSessionHasNoErrors();

        $size = getimagesize(Storage::disk('public')->path(Question::query()->sole()->image));
        $this->assertSame([512, 256], [$size[0], $size[1]]);
    }

    public function test_archiving_deletes_the_image_file_unless_another_question_uses_it(): void
    {
        Storage::disk('public')->put('questions/orange.png', 'png');
        Storage::disk('public')->put('questions/mtn.png', 'png');
        $orange = $this->logoQuestion('Orange', 'questions/orange.png');
        $mtn = $this->logoQuestion('MTN', 'questions/mtn.png');
        $this->logoQuestion('MTN (copie)', 'questions/mtn.png');

        $this->delete(route('admin.questions.destroy', $orange))->assertRedirect(route('admin.logos.index'));
        $this->delete(route('admin.questions.destroy', $mtn))->assertRedirect(route('admin.logos.index'));

        $this->assertSoftDeleted($orange);
        $this->assertNull($orange->fresh()->image);
        Storage::disk('public')->assertMissing('questions/orange.png');
        Storage::disk('public')->assertExists('questions/mtn.png');
    }

    public function test_logos_page_shows_the_grid_and_flags_missing_sources(): void
    {
        Storage::disk('public')->put('questions/orange.png', 'png');
        $this->logoQuestion('Orange', 'questions/orange.png', 'Site officiel, novembre 2026');
        $this->logoQuestion('MTN', 'questions/absent.png');

        $this->get(route('admin.logos.index'))->assertOk()
            ->assertSee('Orange')
            ->assertSee('MTN')
            ->assertSee('1 logo(s) sans source')
            ->assertSee('Image manquante');

        $this->get(route('admin.logos.index', ['source' => 'missing']))->assertOk()
            ->assertSee('MTN')
            ->assertDontSee('Site officiel, novembre 2026');

        $this->get(route('admin.logos.index', ['q' => 'oran']))->assertOk()
            ->assertSee('Orange')
            ->assertDontSee('MTN');

        $this->get(route('admin.logos.create'))->assertOk()->assertSee('Importer des logos');
        $this->get(route('admin.questions.create', ['logo' => 1]))->assertOk()
            ->assertSee('Nouveau logo')
            ->assertSee(Question::LOGO_QUESTION_TEXT);
    }

    public function test_bulk_import_creates_one_question_per_file_and_skips_known_brands(): void
    {
        $orange = $this->logoQuestion('Orange', 'questions/orange.png');

        $this->post(route('admin.logos.store'), [
            'logos' => [
                UploadedFile::fake()->image('MTN.png', 256, 256),
                UploadedFile::fake()->image('Moov_Africa.png', 256, 256),
                UploadedFile::fake()->image('Coca-Cola.png', 256, 256),
                UploadedFile::fake()->image('orange.png', 256, 256),
            ],
            'question_text' => Question::LOGO_QUESTION_TEXT,
            'difficulty' => 'easy',
            'image_source' => 'Sites officiels, novembre 2026',
            'is_active' => '0',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.logos.index'))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, '3 logo(s) importé(s).') && str_contains($message, 'ignorés : orange'));

        $imported = Question::query()->with('answers')->whereKeyNot($orange->id)->get();
        $this->assertCount(3, $imported);

        foreach ($imported as $question) {
            $correct = $question->answers->where('is_correct', true)->pluck('answer_text')->all();
            $this->assertContains($correct[0], ['MTN', 'Moov Africa', 'Coca-Cola']);
            $this->assertCount(4, $question->answers);
            $this->assertCount(4, $question->answers->pluck('answer_text')->map(fn ($text) => mb_strtolower($text))->unique());
            $this->assertNull($question->level_id);
            $this->assertSame($this->logos->id, $question->category_id);
            $this->assertSame('image', $question->type);
            $this->assertFalse($question->is_active);
            $this->assertSame('Sites officiels, novembre 2026', $question->image_source);
            Storage::disk('public')->assertExists($question->image);
        }
    }

    public function test_bulk_import_needs_at_least_two_brands(): void
    {
        $this->post(route('admin.logos.store'), [
            'logos' => [UploadedFile::fake()->image('MTN.png', 256, 256)],
            'question_text' => Question::LOGO_QUESTION_TEXT,
            'difficulty' => 'easy',
        ])->assertSessionHasErrors('logos');

        $this->assertDatabaseCount('questions', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_bulk_import_names_the_rejected_file(): void
    {
        $this->post(route('admin.logos.store'), [
            'logos' => [
                UploadedFile::fake()->image('MTN.png', 256, 256),
                UploadedFile::fake()->image('Orange.jpg', 256, 256),
            ],
            'question_text' => Question::LOGO_QUESTION_TEXT,
            'difficulty' => 'easy',
        ])->assertSessionHasErrors(['logos.1' => 'Le fichier « Orange.jpg » doit être de type : png, webp.']);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_map_sessions_leave_logos_out_even_with_a_level(): void
    {
        $quiz = Question::query()->create(['level_id' => $this->level->id, 'question_text' => 'Capitale ?', 'is_active' => true]);
        $quiz->answers()->create(['answer_text' => 'Yamoussoukro', 'is_correct' => true, 'order' => 1]);
        $logo = $this->logoQuestion('Orange', 'questions/orange.png');
        $logo->update(['level_id' => $this->level->id]);

        $session = GameSession::query()->create([
            'user_id' => User::factory()->create()->id,
            'region_id' => $this->level->city->region_id,
            'city_id' => $this->level->city_id,
            'level_id' => $this->level->id,
            'mode' => 'level',
            'status' => 'started',
            'total_questions' => 10,
            'started_at' => now(),
        ]);

        $questions = app(GameSessionService::class)->getQuestionsForSession($session);

        $this->assertSame([$quiz->id], $questions->pluck('id')->all());
    }

    private function logoQuestion(string $brand, string $image, ?string $source = null): Question
    {
        $question = Question::query()->create([
            'category_id' => $this->logos->id,
            'question_text' => Question::LOGO_QUESTION_TEXT,
            'type' => 'image',
            'image' => $image,
            'image_source' => $source,
            'is_active' => true,
        ]);
        $question->answers()->create(['answer_text' => $brand, 'is_correct' => true, 'order' => 1]);

        return $question;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'level_id' => '',
            'category_id' => $this->logos->id,
            'question_text' => Question::LOGO_QUESTION_TEXT,
            'type' => 'image',
            'difficulty' => 'easy',
            'points' => 10,
            'xp_reward' => 5,
            'time_limit' => 20,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('logo.png', 256, 256),
            'answers' => [['text' => 'Orange'], ['text' => 'MTN']],
            'correct' => '0',
        ], $overrides);
    }
}
