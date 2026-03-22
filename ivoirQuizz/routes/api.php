<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/test-quiz-auth', function (Illuminate\Http\Request $request) {
    $categoryId = $request->query('category_id', 1);
    $mode = $request->query('mode', 'category');
    
    $query = \App\Models\Question::where('is_active', true)
        ->with(['options' => function($q) {
            $q->select('id', 'question_id', 'option_text');
        }]);
    
    if ($mode === 'category' && $categoryId) {
        $query->where('category_id', $categoryId);
    }
    
    $questions = $query->inRandomOrder()->limit(10)->get();
    
    return response()->json([
        'success' => true,
        'count' => $questions->count(),
        'category_id' => $categoryId,
        'mode' => $mode,
        'data' => $questions,
    ]);
});

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('api.auth.register');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('api.auth.login');

    Route::post('/google', [AuthController::class, 'googleAuth'])
        ->name('api.auth.google');
});

Route::middleware('auth:sanctum')->group(function (): void {



    Route::prefix('auth')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('api.auth.logout');
        Route::get('/me', [AuthController::class, 'me'])
            ->name('api.auth.me');
    });

    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('api.categories.index');

    Route::get('/quiz/questions', [QuizController::class, 'getQuestions'])
        ->middleware('throttle:quiz')
        ->name('api.quiz.questions');

    Route::prefix('sessions')->group(function (): void {
        Route::post('/', [GameSessionController::class, 'store'])
            ->name('api.sessions.store');
        Route::get('/', [GameSessionController::class, 'index'])
            ->name('api.sessions.index');
        Route::get('/{id}', [GameSessionController::class, 'show'])
            ->name('api.sessions.show');
    });

    Route::prefix('leaderboard')->group(function (): void {
        Route::get('/global', [LeaderboardController::class, 'global'])
            ->name('api.leaderboard.global');
        Route::get('/friends', [LeaderboardController::class, 'friends'])
            ->name('api.leaderboard.friends');
    });

    Route::prefix('friends')->group(function (): void {
        Route::get('/', [FriendshipController::class, 'index'])
            ->name('api.friends.index');
        Route::get('/requests', [FriendshipController::class, 'requests'])
            ->name('api.friends.requests');
        Route::post('/add', [FriendshipController::class, 'add'])
            ->middleware('throttle:add-friend')
            ->name('api.friends.add');
        Route::put('/{id}/accept', [FriendshipController::class, 'accept'])
            ->name('api.friends.accept');
        Route::delete('/{id}', [FriendshipController::class, 'destroy'])
            ->name('api.friends.destroy');
    });

    Route::prefix('profile')->group(function (): void {
        Route::put('/', [ProfileController::class, 'update'])
            ->name('api.profile.update');
        Route::get('/stats', [ProfileController::class, 'stats'])
            ->name('api.profile.stats');
    });
});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée',
    ], 404);

   
});


