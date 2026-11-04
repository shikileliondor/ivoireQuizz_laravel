<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\StoresImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegionRequest;
use App\Models\Region;
use App\Services\Game\GameCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegionController extends Controller
{
    use StoresImages;

    public function __construct(private readonly GameCacheService $cache) {}

    public function index(): View
    {
        return view('admin.regions.index', [
            'regions' => Region::query()->withCount(['cities', 'levels'])->orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.regions.form', ['region' => new Region(['is_active' => true, 'order' => Region::query()->max('order') + 1])]);
    }

    public function store(RegionRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeImage($request, 'image', 'regions', null);

        Region::query()->create($data);
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.regions.index')->with('success', 'Région créée.');
    }

    public function edit(Region $region): View
    {
        return view('admin.regions.form', ['region' => $region]);
    }

    public function update(RegionRequest $request, Region $region): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeImage($request, 'image', 'regions', $region->image);

        $region->update($data);
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.regions.index')->with('success', 'Région mise à jour.');
    }

    public function destroy(Region $region): RedirectResponse
    {
        if ($region->cities()->exists()) {
            return back()->with('error', 'Cette région contient des villes : désactivez-la plutôt que de la supprimer.');
        }

        $region->delete();
        $this->cache->clearRegionsMapCache();

        return redirect()->route('admin.regions.index')->with('success', 'Région supprimée.');
    }
}
