<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddFriendRequest;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FriendshipController extends Controller
{
    /**
     * Return all accepted friends of authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $friendships = Friendship::query()
            ->accepted()
            ->where(function ($query) use ($userId): void {
                $query->where('requester_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->with(['requester:id,name,avatar_id,total_score', 'receiver:id,name,avatar_id,total_score'])
            ->get();

        $friends = $friendships->map(function (Friendship $friendship) use ($userId) {
            $friend = $friendship->requester_id === $userId ? $friendship->receiver : $friendship->requester;

            return [
                'id' => $friend?->id,
                'name' => $friend?->name,
                'avatar_id' => $friend?->avatar_id,
                'total_score' => $friend?->total_score,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'friends' => $friends,
            ],
            'message' => 'Liste des amis récupérée.',
        ]);
    }

    /**
     * Return pending friendship requests received by authenticated user.
     */
    public function requests(Request $request): JsonResponse
    {
        $requests = Friendship::query()
            ->pending()
            ->where('receiver_id', $request->user()->id)
            ->with('requester:id,name,avatar_id,total_score')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => $requests,
            ],
            'message' => 'Demandes d\'ami en attente récupérées.',
        ]);
    }

    /**
     * Create a friendship request from a friend code.
     */
    public function add(AddFriendRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        try {
            $friend = User::query()->where('friend_code', $validated['friend_code'])->first();

            if (! $friend) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code ami invalide',
                    'errors' => null,
                ], 404);
            }

            if ((int) $friend->id === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas vous ajouter vous-même',
                    'errors' => null,
                ], 422);
            }

            $existing = Friendship::query()
                ->where(function ($query) use ($user, $friend): void {
                    $query->where('requester_id', $user->id)
                        ->where('receiver_id', $friend->id);
                })
                ->orWhere(function ($query) use ($user, $friend): void {
                    $query->where('requester_id', $friend->id)
                        ->where('receiver_id', $user->id);
                })
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une demande existe déjà',
                    'errors' => null,
                ], 422);
            }

            $friendship = Friendship::create([
                'requester_id' => $user->id,
                'receiver_id' => $friend->id,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'friendship' => $friendship,
                ],
                'message' => 'Demande d\'ami envoyée.',
            ], 201);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la demande d\'ami.',
                'errors' => [
                    'exception' => $exception->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Accept a pending friendship request.
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        try {
            $friendship = Friendship::query()->findOrFail($id);

            if ((int) $friendship->receiver_id !== (int) $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action non autorisée.',
                    'errors' => null,
                ], 403);
            }

            if (! $friendship->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La demande ne peut pas être acceptée.',
                    'errors' => null,
                ], 422);
            }

            $friendship->update(['status' => 'accepted']);

            return response()->json([
                'success' => true,
                'data' => [
                    'friendship' => $friendship,
                ],
                'message' => 'Demande d\'ami acceptée.',
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'acceptation de la demande.',
                'errors' => [
                    'exception' => $exception->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Delete a friendship relation.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $friendship = Friendship::query()->findOrFail($id);
            $userId = (int) $request->user()->id;

            if (! in_array($userId, [(int) $friendship->requester_id, (int) $friendship->receiver_id], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action non autorisée.',
                    'errors' => null,
                ], 403);
            }

            $friendship->delete();

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Relation supprimée avec succès.',
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la relation.',
                'errors' => [
                    'exception' => $exception->getMessage(),
                ],
            ], 500);
        }
    }
}
