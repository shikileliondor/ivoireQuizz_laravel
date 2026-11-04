<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChestController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CollectibleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GameSessionController;
use App\Http\Controllers\Admin\LeagueController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\PushAutomationController;
use App\Http\Controllers\Admin\PushNotificationController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuestionReportController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Back-office web (session) : préfixe /admin et noms admin.* appliqués dans bootstrap/app.php.
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
    Route::post('/users/{user}/balance', [UserController::class, 'adjustBalance'])->name('users.balance');
    Route::patch('/users/{user}/admin', [UserController::class, 'toggleAdmin'])->name('users.admin');
    Route::delete('/users/{user}/tokens', [UserController::class, 'revokeTokens'])->name('users.tokens');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::resource('regions', RegionController::class)->except('show');
    Route::resource('cities', CityController::class)->except('show');
    Route::resource('levels', LevelController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');

    Route::resource('questions', QuestionController::class)->except('show');
    Route::patch('/questions/{question}/toggle', [QuestionController::class, 'toggle'])->name('questions.toggle');

    Route::get('/reports', [QuestionReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [QuestionReportController::class, 'show'])->name('reports.show');
    Route::patch('/reports/{report}', [QuestionReportController::class, 'update'])->name('reports.update');

    Route::get('/notifications', [PushNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications', [PushNotificationController::class, 'store'])->middleware('throttle:10,1')->name('notifications.store');
    Route::patch('/notifications/{notification}/cancel', [PushNotificationController::class, 'cancel'])->name('notifications.cancel');

    Route::get('/automations', [PushAutomationController::class, 'index'])->name('automations.index');
    Route::put('/automations/settings', [PushAutomationController::class, 'updateSettings'])->name('automations.settings');
    Route::get('/automations/{automation}/edit', [PushAutomationController::class, 'edit'])->name('automations.edit');
    Route::put('/automations/{automation}', [PushAutomationController::class, 'update'])->name('automations.update');
    Route::post('/automations/{automation}/test', [PushAutomationController::class, 'test'])->middleware('throttle:10,1')->name('automations.test');

    Route::get('/sessions', [GameSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [GameSessionController::class, 'show'])->name('sessions.show');

    Route::resource('chests', ChestController::class)->except('show');
    Route::resource('collectibles', CollectibleController::class)->except('show');
    Route::resource('leagues', LeagueController::class)->except('show');
});
