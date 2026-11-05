<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TestCase;

class AdminGameplaySettingTest extends TestCase
{
    public function test_admin_can_customize_coin_and_chrono_rules(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->put(route('admin.gameplay.update'), [
            'hint_enabled' => 1, 'hint_cost' => 12, 'skip_enabled' => 1, 'skip_cost' => 34,
            'chrono_seconds' => 10, 'chrono_questions' => 15, 'chrono_pause_on_hint' => 1,
            'chrono_hint_pause_seconds' => 7,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gameplay_settings', ['key' => 'hint_cost', 'value' => '12']);
        $this->actingAs($admin)->get(route('admin.gameplay.edit'))->assertOk()->assertSee('Pièces et mode Chrono');
    }
}
