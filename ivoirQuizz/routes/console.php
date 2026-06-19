<?php

use App\Jobs\RefreshLeagueRanksJob;
use App\Services\Game\GameCacheService;
use App\Services\Game\LeagueService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
