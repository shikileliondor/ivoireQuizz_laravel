<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LevelRequest;
use App\Models\City;
use App\Models\Level;
use App\Services\Game\GameCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LevelController extends Controller
{
    public function __construct(private readonly GameCacheService $cache) {}

    public function index(Request $request): View
    {
        $levels = Level::query()
            ->with('city.region')
            ->withCount([
                'questions',
                'questions as active_questions_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->when($request->filled('city'), fn ($query) => $query->where('city_id', $request->integer('city')))
            ->when($request->filled('difficulty'), fn ($query) => $query->where('difficulty', $request->query('difficulty')))
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy('city_id')
            ->orderBy('order')
            ->paginate(25)
            ->withQueryString();

        return view('admin.levels.index', [
            'levels' => $levels,
            'cities' => $this->cities(),
            'difficulties' => LevelRequest::DIFFICULTIES,
        ]);
    }

    public function create(Request $request): View
    {
        $cityId = $request->integer('city') ?: null;

        return view('admin.levels.form', [
            'level' => new Level([
                'city_id' => $cityId,
                'difficulty' => 'easy',
                'order' => $cityId ? Level::query()->where('city_id', $cityId)->max('order') + 1 : 1,
                'required_xp' => 0,
                'questions_count' => 10,
                'passing_score' => 70,
                'xp_reward' => 50,
                'coins_reward' => 0,
                'gems_reward' => 0,
                'is_active' => true,
            ]),
            'cities' => $this->cities(),
            'difficulties' => LevelRequest::DIFFICULTIES,
        ]);
    }

    public function store(LevelRequest $request): RedirectResponse
    {
        Level::query()->create($request->validated());
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.levels.index')->with('success', 'Niveau créé.');
    }

    public function edit(Level $level): View
    {
        return view('admin.levels.form', [
            'level' => $level,
            'cities' => $this->cities(),
            'difficulties' => LevelRequest::DIFFICULTIES,
        ]);
    }

    public function update(LevelRequest $request, Level $level): RedirectResponse
    {
        $level->update($request->validated());
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.levels.index')->with('success', 'Niveau mis à jour.');
    }

    public function destroy(Level $level): RedirectResponse
    {
        if ($level->questions()->exists()) {
            return back()->with('error', 'Ce niveau contient des questions : désactivez-le plutôt que de le supprimer.');
        }

        $level->delete();
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.levels.index')->with('success', 'Niveau supprimé.');
    }

    private function cities(): Collection
    {
        return City::query()->with('region:id,name')->orderBy('region_id')->orderBy('order')->get(['id', 'name', 'region_id']);
    }
}
