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
        $jsonTooManyAttempts = static fn () => response()->json(['message' => 'Too Many Attempts.'], 429);

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

        RateLimiter::for('auth', function (Request $request) use ($limitKey): Limit {
            return Limit::perMinute(5)
                ->by($limitKey($request))
                ->response(function () {
                    return response()->json([
                        'message' => 'Trop de tentatives, réessayez dans 1 minute',
                    ], 429);
                });
        });

        RateLimiter::for('add-friend', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(10)->by($limitKey($request))->response($jsonTooManyAttempts);
        });

        RateLimiter::for('quiz', function (Request $request) use ($limitKey, $jsonTooManyAttempts): Limit {
            return Limit::perMinute(30)->by($limitKey($request))->response($jsonTooManyAttempts);
        });
    }
}
