<?php

namespace App\Services\Notifications;

use App\Enums\GameConstants;
use App\Enums\UserStatus;
use App\Models\LeagueMember;
use App\Models\LeagueSeason;
use App\Models\PushAutomation;
use App\Models\PushLog;
use App\Models\PushNotification;
use App\Models\Question;
use App\Models\User;
use App\Models\UserChest;
use App\Services\Admin\PushNotificationService;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Exécuté chaque minute par le planificateur (notifications:tick) : lance les envois programmés
 * du back-office et les relances automatiques qui sont dues.
 *
 * Chaque relance produit des « candidats » par paquets de 500 joueurs, puis passe par les mêmes filtres :
 * pas deux fois la même (type + ref), pas plus de N relances par joueur et par jour.
 */
class ReminderService
{
    public const HEARTBEAT_KEY = 'push:scheduler_heartbeat';

    /** Une relance quotidienne manquée (planificateur arrêté) peut encore partir pendant ce délai. */
    private const DAILY_GRACE_HOURS = 2;

    public function __construct(
        private readonly PushSender $sender,
        private readonly PushNotificationService $campaigns,
    ) {}

    /** @return array<string, int> notifications mises en file, par type */
    public function tick(?Carbon $now = null): array
    {
        $now ??= now();
        Cache::forever(self::HEARTBEAT_KEY, $now->timestamp);

        PushNotification::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->each(function (PushNotification $notification): void {
                try {
                    $this->campaigns->dispatch($notification);
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        if ($this->isQuietTime($now)) {
            return [];
        }

        $queued = [];
        foreach (array_keys(PushCatalog::reminders()) as $key) {
            $automation = PushAutomation::for($key);
            if (! $automation->enabled || ! $this->isDue($automation, $now)) {
                continue;
            }

            // Marquée avant l'envoi : une erreur ne doit pas faire repartir la relance à chaque minute.
            $automation->forceFill(['last_run_at' => $now])->save();
            try {
                $queued[$key] = $this->run($automation, $now);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $queued;
    }

    /**
     * État du planificateur affiché dans l'admin : sans tâche Cron, rien de programmé ne part.
     *
     * @return array{last_run: ?Carbon, healthy: bool, queue: string, pending_jobs: ?int, failed_jobs: int, command: string}
     */
    public function status(): array
    {
        $heartbeat = Cache::get(self::HEARTBEAT_KEY);
        $lastRun = $heartbeat ? Carbon::createFromTimestamp($heartbeat, config('app.timezone')) : null;
        $queue = (string) config('queue.default');

        return [
            'last_run' => $lastRun,
            // Cron toutes les 5 minutes : au-delà de 10 minutes sans passage, il est arrêté.
            'healthy' => $lastRun !== null && $lastRun->gt(now()->subMinutes(10)),
            'queue' => $queue,
            'pending_jobs' => $queue === 'database' ? DB::table(config('queue.connections.database.table', 'jobs'))->count() : null,
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'command' => config('services.cron.php').' '.base_path('artisan').' schedule:run >> /dev/null 2>&1',
        ];
    }

    public function isQuietTime(Carbon $now): bool
    {
        ['quiet_start' => $start, 'quiet_end' => $end] = PushAutomation::globals();

        return match (true) {
            $start === $end => false,
            $start > $end => $now->hour >= $start || $now->hour < $end,
            default => $now->hour >= $start && $now->hour < $end,
        };
    }

    private function isDue(PushAutomation $automation, Carbon $now): bool
    {
        $hour = $automation->setting('hour');
        if ($hour !== null) {
            $at = $now->copy()->setTime((int) $hour, 0);

            return $now->gte($at)
                && $now->lt($at->copy()->addHours(self::DAILY_GRACE_HOURS))
                && ($automation->last_run_at === null || $automation->last_run_at->lt($at));
        }

        $every = (int) ($automation->definition()['every'] ?? 60);

        return $automation->last_run_at === null
            || $automation->last_run_at->lte($now->copy()->subMinutes($every)->addSeconds(30));
    }

    private function run(PushAutomation $automation, Carbon $now): int
    {
        $candidates = match ($automation->key) {
            'morning' => $this->morning($automation, $now),
            'streak_risk' => $this->streakRisk($automation, $now),
            'lives_full' => $this->livesFull($now),
            'chest_reminder' => $this->chestReminder($automation, $now),
            'challenge_reminder' => $this->challengeReminder($automation, $now),
            'season_ending' => $this->seasonEnding($automation, $now),
        };

        $queued = 0;
        foreach ($candidates as $batch) {
            $batch = $this->underDailyCap($this->withoutDuplicates($automation->key, $batch), $now);
            $queued += $this->sender->queue($automation, array_map(fn (array $candidate) => $this->sender->message(
                $automation,
                $candidate['user'],
                $candidate['segment'] ?? 'default',
                $candidate['vars'] ?? [],
                $candidate['data'] ?? [],
                $candidate['ref'] ?? null,
            ), $batch));
        }

        return $queued;
    }

    /** Joueurs actifs qui ont au moins un appareil enregistré. */
    private function reachableUsers(): Builder
    {
        return User::query()->where('status', UserStatus::Active->value)->whereHas('deviceTokens');
    }

    /**
     * Le texte dépend du parcours du joueur ; plus il décroche, plus les rappels s'espacent.
     */
    private function morning(PushAutomation $automation, Carbon $now): Generator
    {
        $settings = $automation->settingsWithDefaults();
        $today = $now->copy()->startOfDay();
        $question = $settings['question_of_day'] ? $this->questionOfTheDay($today) : null;
        $window = max($settings['missed_every'], $settings['dormant_every']);

        foreach ($this->reachableUsers()->with('userStreaks')->lazyById(PushSender::CHUNK)->chunk(PushSender::CHUNK) as $users) {
            $lastSent = PushLog::query()
                ->where('type', $automation->key)
                ->whereIn('user_id', $users->pluck('id'))
                ->where('created_at', '>=', $today->copy()->subDays($window))
                ->groupBy('user_id')
                ->selectRaw('user_id, MAX(created_at) as last_sent')
                ->pluck('last_sent', 'user_id');

            $batch = [];
            foreach ($users as $user) {
                $pick = $this->morningSegment($user, $lastSent[$user->id] ?? null, $today, $settings, $question);
                if ($pick !== null) {
                    $batch[] = ['user' => $user, 'segment' => $pick[0], 'vars' => $pick[1], 'ref' => $today->toDateString()];
                }
            }

            yield $batch;
        }
    }

    /** @return array{0: string, 1: array}|null */
    private function morningSegment(User $user, ?string $lastSent, Carbon $today, array $settings, ?string $question): ?array
    {
        if ($user->last_activity_date?->isSameDay($today)) {
            return null;
        }

        if (self::daysBetween($user->created_at, $today) < $settings['discovery_days']) {
            return ['discovery', []];
        }

        $streak = $user->userStreaks;
        if ($streak && $streak->current_streak > 0 && $streak->last_played_date?->isSameDay($today->copy()->subDay())) {
            return ['streak', ['serie' => $streak->current_streak]];
        }

        $absent = self::daysBetween($user->last_activity_date ?? $user->created_at, $today);
        if ($absent >= $settings['stop_after']) {
            return null;
        }

        foreach (['dormant' => ['dormant_after', 'dormant_every'], 'missed' => ['missed_after', 'missed_every']] as $segment => [$after, $every]) {
            if ($absent >= $settings[$after]) {
                $recentlySent = $lastSent !== null && self::daysBetween($lastSent, $today) < $settings[$every];

                return $recentlySent ? null : [$segment, ['jours' => $absent]];
            }
        }

        return $question !== null ? ['question', ['question' => $question]] : ['daily', []];
    }

    /** Une question texte courte, la même pour tous les joueurs de la journée. */
    private function questionOfTheDay(Carbon $today): ?string
    {
        return Cache::remember('push:question:'.$today->toDateString(), $today->copy()->addDay(), fn () => Question::query()
            ->where('is_active', true)
            ->where('type', 'text')
            ->inRandomOrder()
            ->limit(30)
            ->pluck('question_text')
            ->map(fn (string $text) => trim(strip_tags($text)))
            ->first(fn (string $text) => $text !== '' && mb_strlen($text) <= 160));
    }

    private function streakRisk(PushAutomation $automation, Carbon $now): Generator
    {
        $users = $this->reachableUsers()
            ->whereHas('userStreaks', fn (Builder $streaks) => $streaks
                ->where('current_streak', '>=', (int) $automation->setting('min_streak'))
                ->whereDate('last_played_date', $now->copy()->subDay()->toDateString()))
            ->with('userStreaks');

        foreach ($users->lazyById(PushSender::CHUNK)->chunk(PushSender::CHUNK) as $chunk) {
            yield $chunk->map(fn (User $user) => [
                'user' => $user,
                'vars' => ['serie' => $user->userStreaks->current_streak],
                'ref' => $now->toDateString(),
            ])->values()->all();
        }
    }

    /** Joueurs tombés à 0 vie dont toutes les vies sont revenues depuis moins de 6 h. */
    private function livesFull(Carbon $now): Generator
    {
        $users = $this->reachableUsers()
            ->whereHas('userLives', fn (Builder $lives) => $lives
                ->where('lives', 0)
                ->whereBetween('next_life_at', [$now->copy()->subHours(12), $now]))
            ->with('userLives');

        foreach ($users->lazyById(PushSender::CHUNK)->chunk(PushSender::CHUNK) as $chunk) {
            $batch = [];
            foreach ($chunk as $user) {
                $life = $user->userLives;
                $fullAt = $life->next_life_at->copy()->addMinutes(GameConstants::LIFE_REGENERATION_MINUTES * max(0, $life->max_lives - 1));
                if ($fullAt->lte($now) && $fullAt->gt($now->copy()->subHours(6))) {
                    $batch[] = ['user' => $user, 'vars' => ['vies' => $life->max_lives], 'ref' => (string) $fullAt->timestamp];
                }
            }

            yield $batch;
        }
    }

    /** Un rappel par coffre, et un seul coffre rappelé par joueur à la fois. */
    private function chestReminder(PushAutomation $automation, Carbon $now): Generator
    {
        $chests = UserChest::query()
            ->where('status', GameConstants::CHEST_AVAILABLE)
            ->where('updated_at', '<=', $now->copy()->subHours((int) $automation->setting('after_hours')))
            ->whereIn('user_id', $this->reachableUsers()->select('id'))
            ->with(['user', 'chest']);

        $seen = [];
        foreach ($chests->lazyById(PushSender::CHUNK)->chunk(PushSender::CHUNK) as $chunk) {
            $batch = [];
            foreach ($chunk as $userChest) {
                if (isset($seen[$userChest->user_id])) {
                    continue;
                }
                $seen[$userChest->user_id] = true;
                $batch[] = ['user' => $userChest->user, 'vars' => ['coffre' => $userChest->chest?->name ?? 'coffre'], 'ref' => (string) $userChest->id];
            }

            yield $batch;
        }
    }

    /** L'adversaire n'a pas joué un défi reçu il y a plus de N heures (et moins d'une semaine). */
    private function challengeReminder(PushAutomation $automation, Carbon $now): Generator
    {
        $challenges = DB::table('friend_challenges')
            ->where('status', '!=', 'completed')
            ->whereNull('opponent_score')
            ->whereBetween('created_at', [$now->copy()->subDays(7), $now->copy()->subHours((int) $automation->setting('after_hours'))]);

        foreach ($challenges->lazyById(PushSender::CHUNK)->chunk(PushSender::CHUNK) as $chunk) {
            $opponents = $this->reachableUsers()->whereIn('id', $chunk->pluck('opponent_id'))->get()->keyBy('id');
            $challengers = User::query()->whereIn('id', $chunk->pluck('challenger_id'))->get(['id', 'name', 'pseudo'])
                ->mapWithKeys(fn (User $challenger) => [$challenger->id => $challenger->publicName()]);

            yield $chunk
                ->filter(fn (object $challenge) => $opponents->has($challenge->opponent_id))
                ->map(fn (object $challenge) => [
                    'user' => $opponents[$challenge->opponent_id],
                    'vars' => ['ami' => $challengers[$challenge->challenger_id] ?? 'Ton ami'],
                    'data' => ['code' => $challenge->code],
                    'ref' => (string) $challenge->id,
                ])->values()->all();
        }
    }

    private function seasonEnding(PushAutomation $automation, Carbon $now): Generator
    {
        $seasons = LeagueSeason::query()
            ->where('status', 'active')
            ->whereBetween('ends_at', [$now, $now->copy()->addHours((int) $automation->setting('hours_before'))])
            ->with('league')
            ->get();

        foreach ($seasons as $season) {
            $vars = [
                'ligue' => $season->league?->name ?? 'ligue',
                'heures' => max(1, (int) ceil($now->diffInHours($season->ends_at, true))),
            ];
            $members = $this->reachableUsers()->whereIn('id', LeagueMember::query()->where('league_season_id', $season->id)->select('user_id'));

            foreach ($members->lazyById(PushSender::CHUNK)->chunk(PushSender::CHUNK) as $chunk) {
                yield $chunk->map(fn (User $user) => ['user' => $user, 'vars' => $vars, 'ref' => (string) $season->id])->values()->all();
            }
        }
    }

    /** Retire les joueurs qui ont déjà reçu cette relance pour la même référence. */
    private function withoutDuplicates(string $type, array $batch): array
    {
        $refs = array_filter(array_column($batch, 'ref'), fn ($ref) => $ref !== null);
        if ($refs === []) {
            return $batch;
        }

        $sent = PushLog::query()
            ->where('type', $type)
            ->whereIn('user_id', array_map(fn (array $candidate) => $candidate['user']->id, $batch))
            ->whereIn('ref', array_unique($refs))
            ->get(['user_id', 'ref'])
            ->mapWithKeys(fn (PushLog $log) => [$log->user_id.'|'.$log->ref => true]);

        return array_values(array_filter($batch, fn (array $candidate) => ($candidate['ref'] ?? null) === null
            || ! $sent->has($candidate['user']->id.'|'.$candidate['ref'])));
    }

    /** Retire les joueurs qui ont déjà atteint le plafond de relances du jour. */
    private function underDailyCap(array $batch, Carbon $now): array
    {
        if ($batch === []) {
            return $batch;
        }

        $max = PushAutomation::globals()['max_per_day'];
        $counts = PushLog::query()
            ->where('is_reminder', true)
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->whereIn('user_id', array_map(fn (array $candidate) => $candidate['user']->id, $batch))
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as total')
            ->pluck('total', 'user_id');

        return array_values(array_filter($batch, fn (array $candidate) => (int) ($counts[$candidate['user']->id] ?? 0) < $max));
    }

    private static function daysBetween(mixed $from, Carbon $today): int
    {
        return (int) Carbon::parse($from)->startOfDay()->diffInDays($today, true);
    }
}
