<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameplaySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameplaySettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.gameplay.edit', ['settings' => GameplaySetting::snapshot()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hint_cost' => ['required', 'integer', 'between:0,100000'],
            'skip_cost' => ['required', 'integer', 'between:0,100000'],
            'chrono_seconds' => ['required', 'integer', 'between:5,60'],
            'chrono_questions' => ['required', 'integer', 'between:5,30'],
            'chrono_hint_pause_seconds' => ['required', 'integer', 'between:0,30'],
        ]);
        $values = $validated + [
            'hint_enabled' => $request->boolean('hint_enabled'),
            'skip_enabled' => $request->boolean('skip_enabled'),
            'chrono_pause_on_hint' => $request->boolean('chrono_pause_on_hint'),
        ];
        foreach ($values as $key => $value) {
            GameplaySetting::query()->updateOrCreate(['key' => $key], ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]);
        }
        return back()->with('success', 'Paramètres de jeu enregistrés. Les parties en cours conservent leurs règles.');
    }
}
