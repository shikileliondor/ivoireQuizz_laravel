<?php

namespace App\Services\Admin;

use App\Models\Question;
use App\Support\PublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Repère les questions actives dont l'image est introuvable sur le disque public :
 * l'app les affiche « Image indisponible » (ou ne les propose pas), il faut renvoyer le fichier.
 */
class QuestionImageAudit
{
    /** @return Collection<int, Question> */
    public function missing(): Collection
    {
        return Question::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->where('type', 'image')
                ->orWhere(fn (Builder $query) => $query->whereNotNull('image')->where('image', '!=', '')))
            ->orderBy('id')
            ->get(['id', 'type', 'image', 'question_text'])
            ->reject(fn (Question $question): bool => PublicImage::exists($question->image))
            ->values();
    }

    /**
     * Autres disques locaux où le fichier se trouve (ex. envoyé par erreur sur « local »).
     *
     * @return list<string>
     */
    public function foundElsewhere(?string $path): array
    {
        $path = trim((string) $path);
        if ($path === '' || str_contains('/'.$path, '/.')) {
            return [];
        }

        return collect(config('filesystems.disks'))
            ->filter(fn (array $config, string $name): bool => $name !== 'public' && ($config['driver'] ?? null) === 'local')
            ->keys()
            ->filter(function (string $disk) use ($path): bool {
                try {
                    return Storage::disk($disk)->fileExists($path);
                } catch (Throwable) {
                    return false;
                }
            })
            ->values()
            ->all();
    }
}
