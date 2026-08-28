<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ApiResponse
{
    protected function successResponse(mixed $data = null, string $message = 'Succès', int $code = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data ?? (object) []], $code);
    }

    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return $this->successResponse($data, $message, $status);
    }

    protected function errorResponse(string $message = 'Erreur', array $errors = [], int $code = 400): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => (object) $errors], $code);
    }

    protected function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return $this->errorResponse($message, $errors, $status);
    }

    protected function businessError(Throwable $e, string $context = 'api'): JsonResponse
    {
        Log::warning($context, ['exception' => $e::class, 'message' => $e->getMessage()]);

        return $this->error('L’action demandée ne peut pas être effectuée.', [], 422);
    }
}
