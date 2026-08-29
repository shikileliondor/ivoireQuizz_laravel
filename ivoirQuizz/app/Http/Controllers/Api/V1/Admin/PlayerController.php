<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\PlayerAdjustRequest;
use App\Http\Resources\Api\V1\Admin\AdminPlayerResource;
use App\Models\GameSession;
use App\Models\User;
use App\Models\UserLife;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlayerController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $players = User::query()
            ->with(['userLives', 'userStreaks'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($sub) => $sub->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('friend_code', 'like', $term));
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return AdminPlayerResource::collection($players)->response();
    }

    public function show(User $player)
    {
        $player->load(['userLives', 'userStreaks']);

        $recentSessions = GameSession::query()
            ->where('user_id', $player->id)
            ->with('level:id,title')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'level_id', 'mode', 'status', 'score', 'accuracy', 'created_at']);

        return $this->successResponse([
            'player' => new AdminPlayerResource($player),
            'recent_sessions' => $recentSessions,
        ]);
    }

    public function update(PlayerAdjustRequest $request, User $player)
    {
        try {
            $data = $request->validated();

            DB::transaction(function () use ($data, $player): void {
                if (array_key_exists('lives', $data)) {
                    UserLife::query()->updateOrCreate(
                        ['user_id' => $player->id],
                        ['lives' => $data['lives'], 'next_life_at' => null],
                    );
                    unset($data['lives']);
                }

                if ($data !== []) {
                    $player->update($data);
                }
            });

            Log::info('Admin adjusted player', [
                'admin_id' => $request->user()?->id,
                'player_id' => $player->id,
                'changes' => array_keys($request->validated()),
            ]);

            return $this->successResponse(
                new AdminPlayerResource($player->fresh(['userLives', 'userStreaks'])),
                'Joueur mis à jour.'
            );
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin player update failed');
        }
    }
}
