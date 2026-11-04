<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\City;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
