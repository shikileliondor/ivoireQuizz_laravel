<?php

namespace App\Services\Game;

use App\Exceptions\Game\FriendshipException;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FriendshipService
{
    /**
     * A friendship is one relation, not two: A→B and B→A are the same link seen
     * from either end. Every lookup therefore has to search both columns.
     */
    public function findBetween(User $a, User $b): ?Friendship
    {
        return Friendship::query()
            ->where(fn ($q) => $q->where('requester_id', $a->id)->where('receiver_id', $b->id))
            ->orWhere(fn ($q) => $q->where('requester_id', $b->id)->where('receiver_id', $a->id))
            ->first();
    }

    public function requestByCode(User $user, string $friendCode): Friendship
    {
        $target = User::query()->where('friend_code', mb_strtoupper(trim($friendCode)))->first();

        if (! $target) {
            throw new FriendshipException('Aucun joueur ne correspond à ce code ami.');
        }

        return $this->request($user, $target);
    }

    public function request(User $user, User $target): Friendship
    {
        if ($target->id === $user->id) {
            throw new FriendshipException('Tu ne peux pas t’ajouter toi-même.');
        }

        return DB::transaction(function () use ($user, $target): Friendship {
            $existing = $this->findBetween($user, $target);

            if ($existing) {
                if ($existing->isAccepted()) {
                    throw new FriendshipException('Vous êtes déjà amis.');
                }

                // The other player already asked first: answering with a request
                // of your own is the same as accepting theirs.
                if ($existing->receiver_id === $user->id) {
                    $existing->update(['status' => 'accepted']);

                    return $existing->fresh();
                }

                throw new FriendshipException('Ta demande est déjà en attente.');
            }

            return Friendship::query()->create([
                'requester_id' => $user->id,
                'receiver_id' => $target->id,
                'status' => 'pending',
            ]);
        });
    }

    public function accept(User $user, Friendship $friendship): Friendship
    {
        if ($friendship->receiver_id !== $user->id) {
            Log::warning('Invalid friendship accept attempt', [
                'user_id' => $user->id,
                'friendship_id' => $friendship->id,
            ]);
            throw new FriendshipException('Seul le destinataire peut accepter cette demande.');
        }

        if ($friendship->isAccepted()) {
            return $friendship;
        }

        $friendship->update(['status' => 'accepted']);

        return $friendship->fresh();
    }

    /** Covers refusing a pending request and removing an established friend. */
    public function remove(User $user, Friendship $friendship): void
    {
        if ($friendship->requester_id !== $user->id && $friendship->receiver_id !== $user->id) {
            Log::warning('Invalid friendship delete attempt', [
                'user_id' => $user->id,
                'friendship_id' => $friendship->id,
            ]);
            throw new FriendshipException('Cette relation ne te concerne pas.');
        }

        $friendship->delete();
    }

    /** @return Collection<int, User> */
    public function friendsOf(User $user): Collection
    {
        $friendIds = Friendship::query()
            ->accepted()
            ->where(fn ($q) => $q->where('requester_id', $user->id)->orWhere('receiver_id', $user->id))
            ->get()
            ->map(fn (Friendship $f) => $f->requester_id === $user->id ? $f->receiver_id : $f->requester_id);

        return User::query()->whereIn('id', $friendIds)->orderBy('name')->get();
    }

    /** @return Collection<int, Friendship> */
    public function pendingReceived(User $user): Collection
    {
        return Friendship::query()
            ->pending()
            ->where('receiver_id', $user->id)
            ->with('requester')
            ->latest()
            ->get();
    }

    /** @return Collection<int, Friendship> */
    public function pendingSent(User $user): Collection
    {
        return Friendship::query()
            ->pending()
            ->where('requester_id', $user->id)
            ->with('receiver')
            ->latest()
            ->get();
    }

    /**
     * Ranking among friends, with the player included. Comparing yourself to
     * people you know beats comparing yourself to strangers in a Bronze league.
     *
     * @return list<array{rank: int, is_me: bool, user: User}>
     */
    public function leaderboard(User $user): array
    {
        $everyone = $this->friendsOf($user)->push($user)
            ->sortByDesc(fn (User $u) => [(int) $u->xp_total, (int) $u->total_score])
            ->values();

        return $everyone
            ->map(fn (User $u, int $index): array => [
                'rank' => $index + 1,
                'is_me' => $u->id === $user->id,
                'user' => $u,
            ])
            ->all();
    }
}
