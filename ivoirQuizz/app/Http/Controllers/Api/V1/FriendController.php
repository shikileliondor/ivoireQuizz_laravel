<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Friend\FriendRequestRequest;
use App\Http\Resources\Api\V1\FriendResource;
use App\Http\Resources\Api\V1\FriendshipResource;
use App\Models\Friendship;
use App\Services\Game\FriendshipService;
use Illuminate\Http\Request;
use Throwable;

class FriendController extends Controller
{
    use ApiResponse;

    public function __construct(
        private FriendshipService $friendships,
    ) {}

    public function index(Request $request)
    {
        try {
            return $this->successResponse([
                'friend_code' => $request->user()->friend_code,
                'friends' => FriendResource::collection($this->friendships->friendsOf($request->user())),
            ]);
        } catch (Throwable $e) {
            return $this->businessError($e, 'friends index failed');
        }
    }

    public function requests(Request $request)
    {
        try {
            return $this->successResponse([
                'received' => FriendshipResource::collection($this->friendships->pendingReceived($request->user())),
                'sent' => FriendshipResource::collection($this->friendships->pendingSent($request->user())),
            ]);
        } catch (Throwable $e) {
            return $this->businessError($e, 'friend requests failed');
        }
    }

    public function store(FriendRequestRequest $request)
    {
        try {
            $friendship = $this->friendships->requestByCode(
                $request->user(),
                $request->validated('friend_code'),
            );

            $friendship->load(['requester', 'receiver']);

            $message = $friendship->isAccepted()
                ? 'Vous êtes maintenant amis.'
                : 'Demande envoyée.';

            return $this->successResponse(new FriendshipResource($friendship), $message, 201);
        } catch (Throwable $e) {
            return $this->businessError($e, 'friend request failed');
        }
    }

    public function accept(Request $request, Friendship $friendship)
    {
        try {
            $accepted = $this->friendships->accept($request->user(), $friendship);
            $accepted->load(['requester', 'receiver']);

            return $this->successResponse(new FriendshipResource($accepted), 'Demande acceptée.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'friend accept failed');
        }
    }

    /** Refusing a pending request and removing a friend are the same action. */
    public function destroy(Request $request, Friendship $friendship)
    {
        try {
            $this->friendships->remove($request->user(), $friendship);

            return $this->successResponse(null, 'Relation supprimée.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'friend delete failed');
        }
    }

    public function leaderboard(Request $request)
    {
        try {
            $rows = $this->friendships->leaderboard($request->user());

            return $this->successResponse(array_map(fn (array $row): array => [
                'rank' => $row['rank'],
                'is_me' => $row['is_me'],
                'player' => new FriendResource($row['user']),
            ], $rows));
        } catch (Throwable $e) {
            return $this->businessError($e, 'friend leaderboard failed');
        }
    }
}
