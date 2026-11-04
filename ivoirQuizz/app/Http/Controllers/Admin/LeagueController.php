<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeagueRequest;
use App\Models\League;
use App\Models\LeagueSeason;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeagueController extends Controller
{
    public function index(): View
    {
        return view('admin.leagues.index', [
            'leagues' => League::query()->withCount('seasons')->orderBy('rank_order')->get(),
            'seasons' => LeagueSeason::query()->with('league')->withCount('members')->latest('starts_at')->limit(15)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.leagues.form', ['league' => new League([
            'rank_order' => League::query()->max('rank_order') + 1,
            'color' => '#F77F00',
            'is_active' => true,
        ])]);
    }

    public function store(LeagueRequest $request): RedirectResponse
    {
        League::query()->create($request->validated());

        return redirect()->route('admin.leagues.index')->with('success', 'Ligue créée.');
    }

    public function edit(League $league): View
    {
        return view('admin.leagues.form', ['league' => $league]);
    }

    public function update(LeagueRequest $request, League $league): RedirectResponse
    {
        $league->update($request->validated());

        return redirect()->route('admin.leagues.index')->with('success', 'Ligue mise à jour.');
    }

    public function destroy(League $league): RedirectResponse
    {
        if ($league->seasons()->exists()) {
            return back()->with('error', 'Cette ligue a déjà des saisons : désactivez-la plutôt.');
        }

        $league->delete();

        return redirect()->route('admin.leagues.index')->with('success', 'Ligue supprimée.');
    }
}
