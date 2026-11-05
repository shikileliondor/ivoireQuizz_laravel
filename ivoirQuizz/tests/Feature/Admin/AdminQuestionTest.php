<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\City;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminQuestionTest extends TestCase
{
    use RefreshDatabase;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $region = Region::query()->create(['name' => 'Lagunes', 'slug' => 'lagunes']);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Abidjan', 'slug' => 'abidjan']);
        $this->level = Level::query()->create(['city_id' => $city->id, 'title' => 'Niveau 1', 'slug' => 'niveau-1']);

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_admin_creates_a_question_with_its_answers(): void
    {
        $category = Category::query()->create(['name' => 'Histoire', 'slug' => 'histoire']);

        $this->post(route('admin.questions.store'), $this->payload([
            'category_id' => $category->id,
            'answers' => [
                ['text' => 'Yamoussoukro'],
                ['text' => 'Abidjan'],
                ['text' => ''],
                ['text' => 'Bouaké'],
            ],
            'correct' => '0',
        ]))->assertRedirect(route('admin.questions.index'));

        $question = Question::query()->with('answers')->sole();
        $this->assertSame('Quelle est la capitale politique de la Côte d’Ivoire ?', $question->question_text);
        $this->assertSame($category->id, $question->category_id);
        $this->assertSame(['Yamoussoukro', 'Abidjan', 'Bouaké'], $question->answers->sortBy('order')->pluck('answer_text')->all());
        $this->assertSame(['Yamoussoukro'], $question->answers->where('is_correct', true)->pluck('answer_text')->values()->all());
    }

    public function test_admin_creates_a_logo_question_without_level(): void
    {
        Storage::fake('public');
        $logos = Category::query()->create(['name' => 'Logos', 'slug' => Category::LOGOS]);

        $this->post(route('admin.questions.store'), $this->payload([
            'level_id' => '',
            'category_id' => $logos->id,
            'type' => 'image',
            'question_text' => 'Quelle entreprise utilise ce logo ?',
            'image' => UploadedFile::fake()->image('logo.png', 256, 256),
            'image_source' => 'Site officiel, novembre 2026',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.logos.index'));

        $question = Question::query()->sole();
        $this->assertNull($question->level_id);
        $this->assertSame('Site officiel, novembre 2026', $question->image_source);
        Storage::disk('public')->assertExists($question->image);
    }

    public function test_panel_pages_show_a_logo_question_without_level(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('questions/logo.png', 'png');
        $logos = Category::query()->create(['name' => 'Logos', 'slug' => Category::LOGOS]);
        $question = Question::query()->create([
            'category_id' => $logos->id,
            'question_text' => 'Quelle entreprise utilise ce logo ?',
            'type' => 'image',
            'image' => 'questions/logo.png',
            'image_source' => 'Site officiel, novembre 2026',
        ]);
        $question->answers()->create(['answer_text' => 'Entreprise A', 'is_correct' => true, 'order' => 1]);
        $report = QuestionReport::query()->create([
            'user_id' => User::factory()->create()->id,
            'question_id' => $question->id,
            'reason' => 'typo',
        ]);

        $this->get(route('admin.questions.index'))->assertOk()
            ->assertSee('Quelle entreprise utilise ce logo ?')
            ->assertSee('Lancement rapide');
        $this->get(route('admin.questions.index', ['category' => $logos->id]))->assertOk()
            ->assertSee('Quelle entreprise utilise ce logo ?');
        $this->get(route('admin.questions.edit', $question))->assertOk()
            ->assertSee('Aucun (lancement rapide uniquement)')
            ->assertSee('Source de l’image')
            ->assertSee('Site officiel, novembre 2026')
            ->assertSee('object-contain');
        $this->get(route('admin.questions.create'))->assertOk()
            ->assertSee('Source de l’image');
        $this->get(route('admin.reports.show', $report))->assertOk()
            ->assertSee('Quelle entreprise utilise ce logo ?')
            ->assertSeeInOrder(['Lancement rapide', '· Logos']);
    }

    public function test_image_question_needs_an_image(): void
    {
        $this->post(route('admin.questions.store'), $this->payload(['type' => 'image']))
            ->assertSessionHasErrors('image');
        $this->assertDatabaseCount('questions', 0);

        $question = Question::query()->create(['level_id' => $this->level->id, 'question_text' => 'Logo ?', 'type' => 'image', 'image' => 'questions/logo.png']);

        $this->put(route('admin.questions.update', $question), $this->payload(['type' => 'image']))
            ->assertSessionHasNoErrors();
        $this->put(route('admin.questions.update', $question), $this->payload(['type' => 'image', 'remove_image' => '1']))
            ->assertSessionHasErrors('image');
    }

    public function test_correct_answer_must_point_to_a_filled_row(): void
    {
        $this->from(route('admin.questions.create'))
            ->post(route('admin.questions.store'), $this->payload([
                'answers' => [['text' => 'A'], ['text' => 'B'], ['text' => '']],
                'correct' => '2',
            ]))
            ->assertRedirect(route('admin.questions.create'))
            ->assertSessionHasErrors('correct');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_question_needs_two_distinct_answers(): void
    {
        $this->post(route('admin.questions.store'), $this->payload([
            'answers' => [['text' => 'Abidjan'], ['text' => '']],
            'correct' => '0',
        ]))->assertSessionHasErrors('answers');

        $this->post(route('admin.questions.store'), $this->payload([
            'answers' => [['text' => 'Abidjan'], ['text' => 'abidjan']],
            'correct' => '0',
        ]))->assertSessionHasErrors('answers.1.text');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_update_keeps_existing_answer_ids_and_removes_cleared_rows(): void
    {
        $question = Question::query()->create(['level_id' => $this->level->id, 'question_text' => 'Ancienne question ?']);
        $first = $question->answers()->create(['answer_text' => 'Un', 'is_correct' => true, 'order' => 1]);
        $second = $question->answers()->create(['answer_text' => 'Deux', 'is_correct' => false, 'order' => 2]);
        $third = $question->answers()->create(['answer_text' => 'Trois', 'is_correct' => false, 'order' => 3]);

        $this->put(route('admin.questions.update', $question), $this->payload([
            'question_text' => 'Nouvelle question ?',
            'answers' => [
                ['id' => $first->id, 'text' => 'Un (corrigé)'],
                ['id' => $second->id, 'text' => 'Deux'],
                ['id' => $third->id, 'text' => ''],
                ['text' => 'Quatre'],
            ],
            'correct' => '1',
        ]))->assertRedirect(route('admin.questions.edit', $question));

        $answers = $question->answers()->orderBy('order')->get();
        $this->assertSame('Nouvelle question ?', $question->fresh()->question_text);
        $this->assertSame(['Un (corrigé)', 'Deux', 'Quatre'], $answers->pluck('answer_text')->all());
        $this->assertSame([$first->id, $second->id], $answers->take(2)->pluck('id')->all());
        $this->assertSame([false, true, false], $answers->pluck('is_correct')->all());
        $this->assertModelMissing($third);
    }

    public function test_answer_ids_from_another_question_are_ignored(): void
    {
        $other = Question::query()->create(['level_id' => $this->level->id, 'question_text' => 'Autre ?']);
        $foreign = $other->answers()->create(['answer_text' => 'Intouchable', 'is_correct' => true]);
        $question = Question::query()->create(['level_id' => $this->level->id, 'question_text' => 'Cible ?']);

        $this->put(route('admin.questions.update', $question), $this->payload([
            'answers' => [['id' => $foreign->id, 'text' => 'Piratée'], ['text' => 'B']],
            'correct' => '0',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Intouchable', $foreign->fresh()->answer_text);
        $this->assertSame($other->id, $foreign->fresh()->question_id);
    }

    public function test_toggle_and_archive_question(): void
    {
        $question = Question::query()->create(['level_id' => $this->level->id, 'question_text' => 'Question ?', 'is_active' => true]);

        $this->patch(route('admin.questions.toggle', $question))->assertRedirect();
        $this->assertFalse($question->fresh()->is_active);

        $this->delete(route('admin.questions.destroy', $question))->assertRedirect(route('admin.questions.index'));
        $this->assertSoftDeleted($question);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'level_id' => $this->level->id,
            'question_text' => 'Quelle est la capitale politique de la Côte d’Ivoire ?',
            'type' => 'text',
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
