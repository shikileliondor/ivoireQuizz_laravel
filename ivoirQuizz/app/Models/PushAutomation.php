<?php

namespace App\Models;

use App\Services\Notifications\PushCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Réglages d'un type de notification automatique ; ce qui n'est pas enregistré
 * reprend les valeurs par défaut de PushCatalog.
 */
class PushAutomation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
        'templates' => 'array',
        'last_run_at' => 'datetime',
    ];

    public static function for(string $key): self
    {
        if (PushCatalog::get($key) === null) {
            throw new InvalidArgumentException("Type de notification inconnu : $key");
        }

        return static::query()->firstOrCreate(['key' => $key], ['enabled' => true]);
    }

    /**
     * Réglages communs aux relances (plafond quotidien, heures calmes).
     *
     * @return array{max_per_day: int, quiet_start: int, quiet_end: int}
     */
    public static function globals(): array
    {
        $saved = static::query()->where('key', PushCatalog::GLOBAL_KEY)->value('settings');
        $saved = is_string($saved) ? json_decode($saved, true) : $saved;

        return collect(PushCatalog::GLOBAL_SETTINGS)
            ->map(fn (array $setting, string $name) => (int) ($saved[$name] ?? $setting['default']))
            ->all();
    }

    public function definition(): array
    {
        return PushCatalog::get($this->key);
    }

    public function isReminder(): bool
    {
        return $this->definition()['category'] === PushCatalog::REMINDER;
    }

    public function setting(string $name): mixed
    {
        return $this->settingsWithDefaults()[$name] ?? null;
    }

    public function settingsWithDefaults(): array
    {
        return collect($this->definition()['settings'])
            ->map(fn (array $setting, string $name) => $this->settings[$name] ?? $setting['default'])
            ->all();
    }

    /** @return array{title: string, bodies: list<string>} */
    public function template(string $segment): array
    {
        $default = $this->definition()['segments'][$segment]
            ?? throw new InvalidArgumentException("Variante inconnue : {$this->key}.$segment");
        $saved = $this->templates[$segment] ?? [];

        return [
            'title' => $saved['title'] ?? $default['title'],
            'bodies' => ($saved['bodies'] ?? []) ?: $default['bodies'],
        ];
    }

    /**
     * Titre et texte avec les variables remplacées. Le texte est choisi parmi les variantes selon $seed,
     * pour que deux joueurs ou deux jours de suite ne reçoivent pas toujours le même.
     *
     * @return array{0: string, 1: string}
     */
    public function render(string $segment, array $vars, int $seed = 0): array
    {
        $template = $this->template($segment);
        $bodies = array_values($template['bodies']);
        $fill = fn (string $text) => trim(preg_replace('/\s{2,}/u', ' ', preg_replace_callback(
            '/\{(\w+)\}/u',
            fn (array $match) => (string) ($vars[$match[1]] ?? ''),
            $text,
        )));

        return [
            Str::limit($fill($template['title']), 100),
            Str::limit($fill($bodies[abs($seed) % count($bodies)]), 240),
        ];
    }

    /** Données FCM lues par l'app pour ouvrir le bon écran. */
    public function data(): array
    {
        return $this->definition()['data'];
    }
}
