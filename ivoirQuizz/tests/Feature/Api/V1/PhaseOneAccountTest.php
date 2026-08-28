<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseOneAccountTest extends TestCase
{
    public function test_account_deletion_requires_password_and_explicit_confirmation(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/account', [
            'current_password' => 'wrong-password',
            'confirmation' => 'SUPPRIMER',
        ])->assertUnprocessable()->assertJsonStructure(['errors' => ['current_password']]);

        $this->assertNotSoftDeleted($user);
    }

    public function test_user_can_delete_and_anonymize_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'phone' => '0700000000',
            'bio' => 'Donnée personnelle',
        ]);
        $user->createToken('mobile');
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/account', [
            'current_password' => 'password123',
            'confirmation' => 'SUPPRIMER',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSoftDeleted($user);
        $deleted = User::withTrashed()->findOrFail($user->id);
        $this->assertNull($deleted->phone);
        $this->assertNull($deleted->bio);
        $this->assertStringStartsWith('deleted+', $deleted->email);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
