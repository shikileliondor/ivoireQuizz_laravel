<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\User\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    public function me(Request $request): JsonResponse
    {
        return $this->success('Profil utilisateur.', new UserResource($request->user()));
    }

    public function update(UpdateProfileRequest $request, ProfileService $service): JsonResponse
    {
        $this->authorize('update', $request->user());
        $user = $service->update($request->user(), $request->validated());

        return $this->success('Profil mis à jour.', new UserResource($user));
    }
}
