<?php

namespace Tests\Feature\Seeders;

use App\Models\Category;
use App\Models\Question;
use Database\Seeders\Local\LogoDemoSeeder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class LogoDemoSeederTest extends TestCase
{
    public function test_it_seeds_four_complete_displayable_logos_idempotently(): void
    {
        Storage::fake('public');
        $this->seed(LogoDemoSeeder::class);
        $questionIds = Question::query()->orderBy('id')->pluck('id')->all();
        $answerIds = Question::query()->with('answers')->orderBy('id')->get()
            ->flatMap(fn (Question $question) => $question->answers->sortBy('id')->pluck('id'))
            ->all();

        Question::query()->first()->update([
            'question_text' => 'Quel est ce logo fictif ?',
            'image_source' => 'Source modifiée dans l’administration',
        ]);
        $this->seed(LogoDemoSeeder::class);

        $logos = Question::query()
            ->whereHas('category', fn ($query) => $query->where('slug', Category::LOGOS))
            ->with('answers')
            ->get();

        $this->assertCount(4, $logos);
        $this->assertSame($questionIds, $logos->sortBy('id')->pluck('id')->all());
        $this->assertSame(
            $answerIds,
            $logos->sortBy('id')->flatMap(fn (Question $question) => $question->answers->sortBy('id')->pluck('id'))->all(),
        );
        foreach ($logos as $logo) {
            $this->assertSame('image', $logo->type);
            $this->assertTrue($logo->is_active);
            $this->assertNotEmpty($logo->image_source);
            $this->assertCount(4, $logo->answers);
            $this->assertCount(1, $logo->answers->where('is_correct', true));
            Storage::disk('public')->assertExists($logo->image);
        }
    }

    public function test_it_stops_when_a_demo_image_cannot_be_written(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturnFalse();
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible d’écrire le logo de démonstration');

        $this->seed(LogoDemoSeeder::class);
    }

    public function test_it_refuses_to_seed_in_production(): void
    {
        $originalEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('LogoDemoSeeder est réservé aux environnements local et testing.');

            (new LogoDemoSeeder)->run();
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);
        }
    }
}
