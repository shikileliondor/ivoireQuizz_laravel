<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $limitKey = static fn (Request $request): string => (string) ($request->user()?->id ?: $request->ip());
        $jsonTooManyAttempts = static fn () => response()->json(['success' => false, 'message' => 'Trop de tentatives.', 'errors' => (object) []], 429);

        RateLimiter::for('api', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(120)->by($limitKey($request))->response($jsonTooManyAttempts);
        });

        RateLimiter::for('quiz-answer', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(30)->by($limitKey($request))->response($jsonTooManyAttempts);
        });

        RateLimiter::for('start-game', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(10)->by($limitKey($request))->response($jsonTooManyAttempts);
        });

        RateLimiter::for('open-chest', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(10)->by($limitKey($request))->response($jsonTooManyAttempts);
        });

        RateLimiter::for('report-question', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(5)->by($limitKey($request))->response($jsonTooManyAttempts);
        });

        RateLimiter::for('auth', function (Request $request) use ($limitKey): Limit {
            return Limit::perMinute(5)
                ->by($limitKey($request))
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Trop de tentatives, réessayez dans 1 minute',
                        'errors' => (object) [],
                    ], 429);
                });
        });

        RateLimiter::for('password-reset', function (Request $request) use ($jsonTooManyAttempts): array {
            $email = mb_strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(3)->by($email.'|'.$request->ip())->response($jsonTooManyAttempts),
                Limit::perHour(20)->by($request->ip())->response($jsonTooManyAttempts),
            ];
        });

        RateLimiter::for('add-friend', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(10)->by($limitKey($request))->response($jsonTooManyAttempts);
        });

        RateLimiter::for('quiz', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(30)->by($limitKey($request))->response($jsonTooManyAttempts);
        });
    }
}
