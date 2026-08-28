<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\DeleteAccountRequest;
use App\Services\User\AccountService;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    use ApiResponse;

    public function destroy(DeleteAccountRequest $request, AccountService $service): JsonResponse
    {
        $this->authorize('delete', $request->user());
        $service->delete($request->user());

        return $this->success('Compte supprimé.');
    }
}
