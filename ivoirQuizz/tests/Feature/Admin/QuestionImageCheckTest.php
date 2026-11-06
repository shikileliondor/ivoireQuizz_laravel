<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\User;
use App\Support\PublicImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class QuestionImageCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        Storage::fake('local');
    }

    // ---- Enregistrement depuis l'admin ----

    public function test_an_image_that_could_not_be_stored_is_refused(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->failPublicDiskWrites();

        $this->post(route('admin.questions.store'), $this->payload([
            'image' => UploadedFile::fake()->image('drapeau.png', 200, 200),
        ]))->assertSessionHasErrors(['image' => PublicImage::NOT_STORED_MESSAGE]);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_a_failed_upload_keeps_the_current_image(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Storage::disk('public')->put('questions/actuelle.png', 'png');
        $question = $this->imageQuestion('questions/actuelle.png');
        $this->failPublicDiskWrites();

        $this->put(route('admin.questions.update', $question), $this->payload([
            'image' => UploadedFile::fake()->image('nouvelle.png', 200, 200),
        ]))->assertSessionHasErrors(['image' => PublicImage::NOT_STORED_MESSAGE]);

        $this->assertSame('questions/actuelle.png', $question->fresh()->image);
        Storage::disk('public')->assertExists('questions/actuelle.png');
    }

    public function test_saving_an_image_question_whose_file_is_missing_asks_to_upload_it_again(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $question = $this->imageQuestion('questions/perdue.png');

        $this->put(route('admin.questions.update', $question), $this->payload())
            ->assertSessionHasErrors(['image' => 'L’image actuelle est introuvable sur le serveur : envoyez-la à nouveau.']);

        $this->put(route('admin.questions.update', $question), $this->payload([
            'image' => UploadedFile::fake()->image('retrouvee.png', 200, 200),
        ]))->assertSessionHasNoErrors();

        $this->assertTrue(PublicImage::exists($question->fresh()->image));
    }

    public function test_question_form_flags_a_missing_image(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $question = $this->imageQuestion('questions/perdue.png');

        $this->get(route('admin.questions.edit', $question))
            ->assertOk()
            ->assertSee('Fichier introuvable sur le serveur (questions/perdue.png)');
    }

    // ---- Repérage des images cassées ----

    public function test_admin_lists_active_questions_with_a_missing_image(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        [$ok, $broken, $inactive, $withoutPath] = $this->mixedQuestions();

        $this->get(route('admin.questions.index'))
            ->assertOk()
            ->assertSee('2 question(s) active(s) ont une image introuvable');

        $this->get(route('admin.questions.index', ['image' => 'missing']))
            ->assertOk()
            ->assertSee($broken->question_text)
            ->assertSee($withoutPath->question_text)
            ->assertDontSee($ok->question_text)
            ->assertDontSee($inactive->question_text);
    }

    public function test_check_images_command_lists_broken_questions(): void
    {
        [, $broken, , $withoutPath] = $this->mixedQuestions();
        Storage::disk('local')->put('questions/perdue.png', 'png');

        $this->artisan('questions:check-images')
            ->expectsTable(['ID', 'Type', 'Chemin', 'Trouvé sur', 'Question'], [
                [$broken->id, 'image', 'questions/perdue.png', 'local', Str::limit($broken->question_text, 50)],
                [$withoutPath->id, 'image', '(aucun)', '—', Str::limit($withoutPath->question_text, 50)],
            ])
            ->expectsOutputToContain('2 question(s) active(s) avec une image introuvable')
            ->assertExitCode(1);
    }

    public function test_check_images_command_succeeds_when_every_image_is_present(): void
    {
        Storage::disk('public')->put('questions/ok.png', 'png');
        $this->imageQuestion('questions/ok.png');

        $this->artisan('questions:check-images')
            ->expectsOutput('Toutes les questions actives ont leur image sur le disque public.')
            ->assertExitCode(0);
    }

    /** @return array{Question, Question, Question, Question} */
    private function mixedQuestions(): array
    {
        Storage::disk('public')->put('questions/ok.png', 'png');

        return [
            $this->imageQuestion('questions/ok.png', 'Image présente'),
            $this->imageQuestion('questions/perdue.png', 'Image perdue'),
            $this->imageQuestion('questions/perdue.png', 'Question désactivée', isActive: false),
            $this->imageQuestion(null, 'Type image sans fichier'),
        ];
    }

    private function imageQuestion(?string $image, string $text = 'Quel est ce monument ?', bool $isActive = true): Question
    {
        $question = Question::query()->create(['question_text' => $text, 'type' => 'image', 'image' => $image, 'is_active' => $isActive]);
        $question->answers()->createMany([
            ['answer_text' => 'A', 'is_correct' => true, 'order' => 0],
            ['answer_text' => 'B', 'is_correct' => false, 'order' => 1],
        ]);

        return $question;
    }

    /** Le disque public refuse d'écrire, comme un dossier storage/app/public sans droits. */
    private function failPublicDiskWrites(): void
    {
        $disk = Mockery::mock(Storage::disk('public'))->makePartial();
        $disk->shouldReceive('putFileAs')->andReturn(false);
        Storage::set('public', $disk);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'question_text' => 'Quel est ce monument ?',
            'type' => 'image',
            'difficulty' => 'easy',
            'points' => 10,
            'xp_reward' => 5,
            'time_limit' => 20,
            'is_active' => '1',
            'answers' => [['text' => 'A'], ['text' => 'B']],
            'correct' => '0',
        ], $overrides);
    }
}
