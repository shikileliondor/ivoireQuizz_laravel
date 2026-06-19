<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ApiResponse
{
    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    { return response()->json(['success' => true, 'message' => $message, 'data' => $data ?? (object) []], $status); }

    protected function error(string $message, array $errors = [], int $status = 400): JsonResponse
    { return response()->json(['success' => false, 'message' => $message, 'errors' => (object) $errors], $status); }

    protected function businessError(Throwable $e, string $context = 'api'): JsonResponse
    { Log::warning($context, ['message' => $e->getMessage()]); return $this->error($e->getMessage() ?: 'Erreur métier.', [], 422); }
}
