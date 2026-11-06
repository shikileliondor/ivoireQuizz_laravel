<?php

use App\Enums\UserStatus;
use App\Jobs\RefreshLeagueRanksJob;
use App\Models\PushLog;
use App\Models\Question;
use App\Models\User;
use App\Services\Admin\QuestionImageAudit;
use App\Services\Game\GameCacheService;
use App\Services\Game\LeagueService;
use App\Services\Notifications\ReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('questions:check-images', function (QuestionImageAudit $audit): int {
    $missing = $audit->missing();

    if ($missing->isEmpty()) {
        $this->info('Toutes les questions actives ont leur image sur le disque public.');

        return Command::SUCCESS;
    }

    $this->table(
        ['ID', 'Type', 'Chemin', 'Trouvé sur', 'Question'],
        $missing->map(fn (Question $question): array => [
            $question->id,
            $question->type,
            $question->image ?: '(aucun)',
            implode(', ', $audit->foundElsewhere($question->image)) ?: '—',
            Str::limit((string) $question->question_text, 50),
        ])->all(),
    );
    $this->warn($missing->count().' question(s) active(s) avec une image introuvable. À renvoyer depuis l’admin :');
    $this->line(route('admin.questions.index', ['image' => 'missing']));

    return Command::FAILURE;
})->purpose('Liste les questions actives dont le fichier image est introuvable sur le disque public.');

Artisan::command('game:cache-map', function (GameCacheService $cache): int {
    $regions = $cache->cacheRegionsMap();
    $this->info('Game regions map cached for 6 hours.');
    $this->line('Regions cached: '.count($regions));

    return Command::SUCCESS;
})->purpose('Cache the static game regions map in Redis/cache.');

Artisan::command('game:clear-cache', function (GameCacheService $cache): int {
    $cache->clearRegionsMapCache();
    $this->info('Game cache cleared.');

    return Command::SUCCESS;
})->purpose('Clear static game data cache.');

Artisan::command('admin:create {email} {--name= : Nom affiché (nouveau compte uniquement)} {--password= : Mot de passe, demandé si absent}', function (): int {
    $email = trim((string) $this->argument('email'));

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $this->error('Adresse email invalide.');

        return Command::FAILURE;
    }

    $user = User::query()->where('email', $email)->first();
    $password = $this->option('password');

    if ($user === null && ! $password) {
        $password = $this->secret('Mot de passe du nouvel administrateur (12 caractères minimum)');
    }

    if ($password && mb_strlen($password) < 12) {
        $this->error('Le mot de passe doit contenir au moins 12 caractères.');

        return Command::FAILURE;
    }

    if ($user === null) {
        $user = User::query()->create([
            'name' => $this->option('name') ?: Str::before($email, '@'),
            'email' => $email,
            'password' => Hash::make($password),
            'friend_code' => User::generateFriendCode(),
        ]);
        $this->info("Compte {$email} créé.");
    } elseif ($password) {
        $user->forceFill(['password' => Hash::make($password)])->save();
        $this->info("Mot de passe de {$email} mis à jour.");
    }

    $user->forceFill(['is_admin' => true, 'status' => UserStatus::Active])->save();
    $this->info("{$email} est administrateur. Connexion : ".url('/admin'));

    return Command::SUCCESS;
})->purpose('Create an admin account or promote an existing user to admin.');

Artisan::command('leagues:refresh-ranks {--sync : Run synchronously instead of dispatching a queue job}', function (LeagueService $leagues): int {
    if ($this->option('sync')) {
        $count = $leagues->refreshActiveSeasonRanks();
        $this->info("League ranks refreshed for {$count} active season(s). Redis rankings were rebuilt from MySQL.");

        return Command::SUCCESS;
    }

    RefreshLeagueRanksJob::dispatch();
    $this->info('League rank refresh job dispatched.');

    return Command::SUCCESS;
})->purpose('Refresh league ranks from MySQL and rebuild Redis sorted sets.');

Artisan::command('notifications:tick', function (ReminderService $reminders): int {
    foreach ($reminders->tick() as $type => $count) {
        $this->info("{$type} : {$count} notification(s) mise(s) en file.");
    }

    return Command::SUCCESS;
})->purpose('Send due scheduled push campaigns and automatic reminders (run every minute by the scheduler).');

// Planificateur : une tâche Cron toutes les 5 minutes (ou chaque minute) lance `php artisan schedule:run`
// (voir docs/ADMIN.md). Les tâches « everyMinute » tournent à chaque passage, dans l'ordre :
// les notifications sont mises en file, puis le worker les envoie.
Schedule::command('notifications:tick')->everyMinute()->withoutOverlapping(10);

Schedule::call(fn () => PushLog::query()->where('created_at', '<', now()->subDays(90))->delete())
    ->name('push-logs:prune')
    ->dailyAt('03:00');

// Hébergement mutualisé : pas de worker permanent, la file est vidée à chaque passage (4 min max).
if (config('queue.default') !== 'sync') {
    Schedule::command('queue:work', ['--stop-when-empty', '--max-time=240', '--tries=3'])
        ->everyMinute()
        ->withoutOverlapping(5);
}
