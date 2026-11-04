<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\StoresImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CollectibleRequest;
use App\Models\City;
use App\Models\Collectible;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectibleController extends Controller
{
    use StoresImages;

    public function index(Request $request): View
    {
        $collectibles = Collectible::query()
            ->with(['region', 'city'])
            ->withCount('userCollectibles')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->filled('rarity'), fn ($query) => $query->where('rarity', $request->query('rarity')))
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.collectibles.index', [
            'collectibles' => $collectibles,
            'types' => CollectibleRequest::TYPES,
            'rarities' => CollectibleRequest::RARITIES,
        ]);
    }

    public function create(): View
    {
        return view('admin.collectibles.form', [
            'collectible' => new Collectible(['type' => 'monument', 'rarity' => 'common', 'is_active' => true]),
            ...$this->formOptions(),
        ]);
    }

    public function store(CollectibleRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeImage($request, 'image', 'collectibles', null);

        Collectible::query()->create($data);

        return redirect()->route('admin.collectibles.index')->with('success', 'Objet de collection créé.');
    }

    public function edit(Collectible $collectible): View
    {
        return view('admin.collectibles.form', ['collectible' => $collectible, ...$this->formOptions()]);
    }

    public function update(CollectibleRequest $request, Collectible $collectible): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeImage($request, 'image', 'collectibles', $collectible->image);

        $collectible->update($data);

        return redirect()->route('admin.collectibles.index')->with('success', 'Objet de collection mis à jour.');
    }

    public function destroy(Collectible $collectible): RedirectResponse
    {
        $collectible->delete();

        return redirect()->route('admin.collectibles.index')->with('success', 'Objet de collection archivé.');
    }

    private function formOptions(): array
    {
        return [
            'regions' => Region::query()->orderBy('order')->get(['id', 'name']),
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'types' => CollectibleRequest::TYPES,
            'rarities' => CollectibleRequest::RARITIES,
        ];
    }
}
