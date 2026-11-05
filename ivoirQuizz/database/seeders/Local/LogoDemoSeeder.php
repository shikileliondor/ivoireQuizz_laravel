<?php

namespace Database\Seeders\Local;

use App\Models\Category;
use App\Models\Question;
use App\Services\Admin\QuestionAdminService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LogoDemoSeeder extends Seeder
{
    private const BRANDS = [
        'Baobab Mobile' => 'baobab-mobile.svg',
        'Éléphant Cola' => 'elephant-cola.svg',
        'Ivoire Café' => 'ivoire-cafe.svg',
        "N'Zassa Bank" => 'nzassa-bank.svg',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('LogoDemoSeeder est réservé aux environnements local et testing.');
        }

        $category = Category::query()->updateOrCreate(
            ['slug' => Category::LOGOS],
            ['name' => 'Logos', 'is_active' => true],
        );
        $brands = array_keys(self::BRANDS);

        foreach (self::BRANDS as $brand => $filename) {
            $path = 'questions/demo/'.$filename;
            $source = __DIR__.'/logos/'.$filename;
            if (! is_file($source)) {
                throw new RuntimeException("Ressource de démonstration introuvable : {$filename}.");
            }
            if (! Storage::disk('public')->put($path, file_get_contents($source))) {
                throw new RuntimeException("Impossible d’écrire le logo de démonstration : {$filename}.");
            }

            $question = Question::withTrashed()->firstOrNew(['source_key' => 'local-demo.logo.'.pathinfo($filename, PATHINFO_FILENAME)]);
            if ($question->trashed()) {
                $question->restore();
            }
            $existingAnswers = $question->answers()->get()->keyBy('answer_text');

            $answers = collect($brands)
                ->sortBy(fn (string $candidate): int => $candidate === $brand ? 0 : 1)
                ->values()
                ->map(fn (string $candidate): array => [
                    'id' => $existingAnswers->get($candidate)?->id,
                    'text' => $candidate,
                ])
                ->all();

            app(QuestionAdminService::class)->save($question, [
                'category_id' => $category->id,
                'source_key' => 'local-demo.logo.'.pathinfo($filename, PATHINFO_FILENAME),
                'source_version' => 1,
                'level_id' => null,
                'question_text' => Question::LOGO_QUESTION_TEXT,
                'type' => 'image',
                'difficulty' => 'easy',
                'points' => 10,
                'xp_reward' => 5,
                'time_limit' => 20,
                'image' => $path,
                'image_source' => 'Illustration fictive de démonstration IvoireQuiz, novembre 2026',
                'is_active' => true,
            ], $answers, 0);
        }
    }
}
