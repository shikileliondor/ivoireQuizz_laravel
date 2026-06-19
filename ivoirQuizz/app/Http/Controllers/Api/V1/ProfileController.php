<?php
namespace App\Http\Controllers\Api\V1; use App\Http\Controllers\Controller; use App\Http\Controllers\Api\V1\Concerns\ApiResponse; use App\Http\Resources\Api\V1\UserResource; use Illuminate\Http\Request; class ProfileController extends Controller { use ApiResponse; public function me(Request $request){ return $this->success('Profil utilisateur.', new UserResource($request->user())); }}
