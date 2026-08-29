<?php

namespace Tests\Feature\Api\V1;

use App\Models\Friendship;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FriendshipTest extends TestCase
{
    private function signIn(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_a_request_is_sent_with_a_friend_code(): void
    {
        $me = $this->signIn();
        $other = User::factory()->create(['friend_code' => 'ABC123']);

        $this->postJson('/api/v1/friends/request', ['friend_code' => 'ABC123'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.direction', 'sent');

        $this->assertDatabaseHas('friendships', [
            'requester_id' => $me->id,
            'receiver_id' => $other->id,
            'status' => 'pending',
        ]);
    }

    public function test_an_unknown_code_is_refused(): void
    {
        $this->signIn();

        $this->postJson('/api/v1/friends/request', ['friend_code' => 'ZZZZZZ'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Aucun joueur ne correspond à ce code ami.');
    }

    public function test_a_malformed_code_is_refused_before_any_lookup(): void
    {
        $this->signIn();

        $this->postJson('/api/v1/friends/request', ['friend_code' => 'AB'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('friend_code');
    }

    public function test_a_player_cannot_add_themselves(): void
    {
        $me = $this->signIn(['friend_code' => 'SELF01']);

        $this->postJson('/api/v1/friends/request', ['friend_code' => 'SELF01'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tu ne peux pas t’ajouter toi-même.');

        $this->assertSame(0, Friendship::query()->count());
        $this->assertSame($me->id, $me->fresh()->id);
    }

    public function test_a_duplicate_request_is_refused(): void
    {
        $this->signIn();
        User::factory()->create(['friend_code' => 'ABC123']);

        $this->postJson('/api/v1/friends/request', ['friend_code' => 'ABC123'])->assertCreated();
        $this->postJson('/api/v1/friends/request', ['friend_code' => 'ABC123'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ta demande est déjà en attente.');

        $this->assertSame(1, Friendship::query()->count());
    }

    /**
     * A→B and B→A are the same relation, so answering with your own request
     * must settle it rather than open a second, mirrored one.
     */
    public function test_requesting_back_accepts_the_pending_request(): void
    {
        $me = $this->signIn(['friend_code' => 'MINE01']);
        $other = User::factory()->create(['friend_code' => 'ABC123']);

        Friendship::create(['requester_id' => $other->id, 'receiver_id' => $me->id, 'status' => 'pending']);

        $this->postJson('/api/v1/friends/request', ['friend_code' => 'ABC123'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertSame(1, Friendship::query()->count());
    }

    public function test_only_the_receiver_can_accept(): void
    {
        $me = $this->signIn();
        $other = User::factory()->create();

        $friendship = Friendship::create([
            'requester_id' => $me->id,
            'receiver_id' => $other->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/v1/friends/{$friendship->id}/accept")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Seul le destinataire peut accepter cette demande.');

        $this->assertSame('pending', $friendship->fresh()->status);
    }

    public function test_the_receiver_accepts_and_both_see_the_friend(): void
    {
        $me = $this->signIn();
        $other = User::factory()->create(['name' => 'Awa']);

        $friendship = Friendship::create([
            'requester_id' => $other->id,
            'receiver_id' => $me->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/v1/friends/{$friendship->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->getJson('/api/v1/friends')
            ->assertOk()
            ->assertJsonPath('data.friends.0.name', 'Awa');

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/friends')
            ->assertOk()
            ->assertJsonCount(1, 'data.friends');
    }

    public function test_a_stranger_cannot_delete_someone_elses_friendship(): void
    {
        $this->signIn();
        $a = User::factory()->create();
        $b = User::factory()->create();

        $friendship = Friendship::create(['requester_id' => $a->id, 'receiver_id' => $b->id, 'status' => 'accepted']);

        $this->deleteJson("/api/v1/friends/{$friendship->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cette relation ne te concerne pas.');

        $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
    }

    public function test_either_side_can_remove_the_friendship(): void
    {
        $me = $this->signIn();
        $other = User::factory()->create();

        $friendship = Friendship::create(['requester_id' => $other->id, 'receiver_id' => $me->id, 'status' => 'accepted']);

        $this->deleteJson("/api/v1/friends/{$friendship->id}")->assertOk();

        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function test_pending_requests_are_split_by_direction(): void
    {
        $me = $this->signIn();
        $sender = User::factory()->create();
        $target = User::factory()->create();

        Friendship::create(['requester_id' => $sender->id, 'receiver_id' => $me->id, 'status' => 'pending']);
        Friendship::create(['requester_id' => $me->id, 'receiver_id' => $target->id, 'status' => 'pending']);

        $this->getJson('/api/v1/friends/requests')
            ->assertOk()
            ->assertJsonCount(1, 'data.received')
            ->assertJsonCount(1, 'data.sent')
            ->assertJsonPath('data.received.0.direction', 'received')
            ->assertJsonPath('data.sent.0.direction', 'sent');
    }

    public function test_the_leaderboard_ranks_friends_and_marks_the_player(): void
    {
        $me = $this->signIn(['name' => 'Moi', 'xp_total' => 500]);
        $ahead = User::factory()->create(['name' => 'Devant', 'xp_total' => 900]);
        $behind = User::factory()->create(['name' => 'Derrière', 'xp_total' => 100]);

        Friendship::create(['requester_id' => $me->id, 'receiver_id' => $ahead->id, 'status' => 'accepted']);
        Friendship::create(['requester_id' => $behind->id, 'receiver_id' => $me->id, 'status' => 'accepted']);

        $this->getJson('/api/v1/friends/leaderboard')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.player.name', 'Devant')
            ->assertJsonPath('data.0.is_me', false)
            ->assertJsonPath('data.1.player.name', 'Moi')
            ->assertJsonPath('data.1.is_me', true)
            ->assertJsonPath('data.1.rank', 2)
            ->assertJsonPath('data.2.player.name', 'Derrière');
    }

    public function test_a_lone_player_still_gets_a_leaderboard(): void
    {
        $this->signIn(['name' => 'Solo']);

        $this->getJson('/api/v1/friends/leaderboard')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_me', true);
    }

    /** A friend list must never leak contact details of other players. */
    public function test_the_friend_payload_hides_private_fields(): void
    {
        $me = $this->signIn();
        $other = User::factory()->create(['email' => 'prive@test.com']);
        Friendship::create(['requester_id' => $me->id, 'receiver_id' => $other->id, 'status' => 'accepted']);

        $response = $this->getJson('/api/v1/friends')->assertOk();

        $friend = $response->json('data.friends.0');
        $this->assertArrayNotHasKey('email', $friend);
        $this->assertArrayNotHasKey('phone', $friend);
        $this->assertArrayNotHasKey('password', $friend);
        $this->assertArrayHasKey('xp_total', $friend);
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/v1/friends')->assertStatus(401);
        $this->postJson('/api/v1/friends/request', ['friend_code' => 'ABC123'])->assertStatus(401);
    }
}
