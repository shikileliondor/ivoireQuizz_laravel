<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\StoresImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Models\City;
use App\Models\Region;
use App\Services\Game\GameCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CityController extends Controller
{
    use StoresImages;

    public function __construct(private readonly GameCacheService $cache) {}

    public function index(Request $request): View
    {
        $cities = City::query()
            ->with('region')
            ->withCount('levels')
            ->when($request->filled('region'), fn ($query) => $query->where('region_id', $request->integer('region')))
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy('region_id')
            ->orderBy('order')
            ->paginate(25)
            ->withQueryString();

        return view('admin.cities.index', [
            'cities' => $cities,
            'regions' => Region::query()->orderBy('order')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.cities.form', [
            'city' => new City(['is_active' => true, 'order' => 1, 'region_id' => $request->integer('region') ?: null]),
            'regions' => Region::query()->orderBy('order')->get(['id', 'name']),
        ]);
    }

    public function store(CityRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeImage($request, 'image', 'cities', null);

        City::query()->create($data);
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.cities.index')->with('success', 'Ville créée.');
    }

    public function edit(City $city): View
    {
        return view('admin.cities.form', [
            'city' => $city,
            'regions' => Region::query()->orderBy('order')->get(['id', 'name']),
        ]);
    }

    public function update(CityRequest $request, City $city): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeImage($request, 'image', 'cities', $city->image);

        $city->update($data);
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.cities.index')->with('success', 'Ville mise à jour.');
    }

    public function destroy(City $city): RedirectResponse
    {
        if ($city->levels()->exists()) {
            return back()->with('error', 'Cette ville contient des niveaux : désactivez-la plutôt que de la supprimer.');
        }

        $city->delete();
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.cities.index')->with('success', 'Ville supprimée.');
    }
}
