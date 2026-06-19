<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChestController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\GameMapController;
use App\Http\Controllers\Api\V1\GameSessionController;
use App\Http\Controllers\Api\V1\LeagueController;
use App\Http\Controllers\Api\V1\LifeController;
use App\Http\Controllers\Api\V1\PassportController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\StreakController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/auth/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('/me', [ProfileController::class, 'me'])->name('api.v1.me');

        Route::get('/game/map', [GameMapController::class, 'map'])->name('api.v1.game.map');
        Route::get('/regions/{region}', [GameMapController::class, 'showRegion'])->name('api.v1.regions.show');
        Route::get('/cities/{city}', [GameMapController::class, 'showCity'])->name('api.v1.cities.show');
        Route::get('/levels/{level}', [GameMapController::class, 'showLevel'])->name('api.v1.levels.show');

        Route::post('/levels/{level}/start', [GameSessionController::class, 'start'])->middleware('throttle:start-game')->name('api.v1.levels.start');
        Route::get('/game-sessions/{session}', [GameSessionController::class, 'show'])->name('api.v1.game-sessions.show');
        Route::post('/game-sessions/{session}/answer', [GameSessionController::class, 'answer'])->middleware('throttle:quiz-answer')->name('api.v1.game-sessions.answer');
        Route::post('/game-sessions/{session}/finish', [GameSessionController::class, 'finish'])->name('api.v1.game-sessions.finish');
        Route::post('/game-sessions/{session}/abandon', [GameSessionController::class, 'abandon'])->name('api.v1.game-sessions.abandon');

        Route::get('/lives', [LifeController::class, 'index'])->name('api.v1.lives');
        Route::get('/streak', [StreakController::class, 'show'])->name('api.v1.streak');

        Route::get('/league/current', [LeagueController::class, 'current'])->name('api.v1.league.current');
        Route::get('/league/ranking', [LeagueController::class, 'ranking'])->name('api.v1.league.ranking');

        Route::get('/chests', [ChestController::class, 'index'])->name('api.v1.chests.index');
        Route::post('/chests/{userChest}/open', [ChestController::class, 'open'])->middleware('throttle:open-chest')->name('api.v1.chests.open');

        Route::get('/collection', [CollectionController::class, 'index'])->name('api.v1.collection.index');
        Route::get('/collection/personalities', [CollectionController::class, 'personalities'])->name('api.v1.collection.personalities');
        Route::get('/collection/monuments', [CollectionController::class, 'monuments'])->name('api.v1.collection.monuments');

        Route::get('/passport', [PassportController::class, 'index'])->name('api.v1.passport');
    });
});

Route::fallback(fn () => response()->json(['success' => false, 'message' => 'Route non trouvée', 'errors' => (object) []], 404));
