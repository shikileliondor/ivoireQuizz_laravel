<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushAutomation;
use App\Models\PushLog;
use App\Services\Notifications\PushCatalog;
use App\Services\Notifications\PushSender;
use App\Services\Notifications\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Notifications automatiques : relances (rappel du matin, série, vies…) et notifications d'action.
 */
class PushAutomationController extends Controller
{
    public function index(ReminderService $reminders): View
    {
        $automations = collect(PushCatalog::all())->map(fn (array $definition, string $key) => PushAutomation::for($key));

        return view('admin.automations.index', [
            'reminders' => $automations->only(array_keys(PushCatalog::reminders())),
            'actions' => $automations->only(array_keys(PushCatalog::actions())),
            'counts' => PushLog::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('type')
                ->selectRaw('type, COUNT(*) as total')
                ->pluck('total', 'type'),
            'globals' => PushAutomation::globals(),
            'scheduler' => $reminders->status(),
        ]);
    }

    public function edit(Request $request, string $automation): View
    {
        return view('admin.automations.edit', [
            'automation' => $this->find($automation),
            'mine' => $request->user()->deviceTokens()->count(),
        ]);
    }

    public function update(Request $request, string $automation): RedirectResponse
    {
        $automation = $this->find($automation);
        $definition = $automation->definition();

        $rules = ['enabled' => ['boolean']];
        $attributes = [];
        foreach ($definition['settings'] as $name => $setting) {
            $rules["settings.$name"] = ($setting['type'] ?? null) === 'boolean'
                ? ['boolean']
                : ['required', 'integer', "between:{$setting['min']},{$setting['max']}"];
            $attributes["settings.$name"] = mb_strtolower($setting['label']);
        }
        foreach ($definition['segments'] as $segment => $default) {
            $rules["templates.$segment.title"] = ['required', 'string', 'max:100'];
            $rules["templates.$segment.bodies"] = ['required', 'string', 'max:3000'];
            $attributes["templates.$segment.title"] = "titre « {$default['label']} »";
            $attributes["templates.$segment.bodies"] = "textes « {$default['label']} »";
        }
        $validated = $request->validate($rules, [], $attributes);

        $automation->update([
            'enabled' => $request->boolean('enabled'),
            'settings' => collect($definition['settings'])
                ->map(fn (array $setting, string $name) => ($setting['type'] ?? null) === 'boolean'
                    ? $request->boolean("settings.$name")
                    : (int) $validated['settings'][$name])
                ->all() ?: null,
            // Un texte par ligne ; les lignes vides sont ignorées.
            'templates' => collect($definition['segments'])
                ->map(fn (array $default, string $segment) => [
                    'title' => trim($validated['templates'][$segment]['title']),
                    'bodies' => collect(preg_split('/\R/u', $validated['templates'][$segment]['bodies']))
                        ->map(fn (string $line) => trim($line))
                        ->filter()
                        ->values()
                        ->all(),
                ])
                ->all(),
        ]);

        return redirect()->route('admin.automations.index')->with('success', "« {$definition['label']} » mis à jour.");
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $rules = collect(PushCatalog::GLOBAL_SETTINGS)
            ->map(fn (array $setting) => ['required', 'integer', "between:{$setting['min']},{$setting['max']}"])
            ->all();
        $attributes = collect(PushCatalog::GLOBAL_SETTINGS)->map(fn (array $setting) => mb_strtolower($setting['label']))->all();

        PushAutomation::query()->updateOrCreate(
            ['key' => PushCatalog::GLOBAL_KEY],
            ['settings' => array_map('intval', $request->validate($rules, [], $attributes))],
        );

        return back()->with('success', 'Règles des relances mises à jour.');
    }

    public function test(Request $request, string $automation, PushSender $sender): RedirectResponse
    {
        $automation = $this->find($automation);
        $segment = $request->validate([
            'segment' => ['required', Rule::in(array_keys($automation->definition()['segments']))],
        ])['segment'];

        if (! $request->user()->deviceTokens()->exists()) {
            return back()->with('error', 'Aucun appareil enregistré pour votre compte : connectez-vous à l’app avec ce compte pour recevoir les tests.');
        }

        try {
            $result = $sender->sendTest($automation, $request->user(), $segment);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Envoi impossible : '.$exception->getMessage());
        }

        return back()->with('success', "Test envoyé : reçu sur {$result['sent']} appareil(s) sur ".array_sum($result).'.');
    }

    private function find(string $key): PushAutomation
    {
        abort_if(PushCatalog::get($key) === null, 404);

        return PushAutomation::for($key);
    }
}
