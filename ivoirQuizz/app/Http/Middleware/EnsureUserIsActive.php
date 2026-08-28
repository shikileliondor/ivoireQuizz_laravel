<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== UserStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte n’est pas actif.',
                'errors' => (object) [],
            ], 403);
        }

        return $next($request);
    }
}
