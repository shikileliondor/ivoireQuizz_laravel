<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\StoresImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChestRequest;
use App\Models\Chest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChestController extends Controller
{
    use StoresImages;

    public function index(): View
    {
        return view('admin.chests.index', [
            'chests' => Chest::query()
                ->withCount([
                    'userChests',
                    'userChests as opened_count' => fn ($query) => $query->where('status', 'opened'),
                ])
                ->orderByRaw("CASE type WHEN 'bronze' THEN 1 WHEN 'silver' THEN 2 ELSE 3 END")
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.chests.form', ['chest' => new Chest(['type' => 'bronze', 'is_active' => true])]);
    }

    public function store(ChestRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeImage($request, 'image', 'chests', null);

        Chest::query()->create($data);

        return redirect()->route('admin.chests.index')->with('success', 'Coffre créé.');
    }

    public function edit(Chest $chest): View
    {
        return view('admin.chests.form', ['chest' => $chest]);
    }

    public function update(ChestRequest $request, Chest $chest): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeImage($request, 'image', 'chests', $chest->image);

        $chest->update($data);

        return redirect()->route('admin.chests.index')->with('success', 'Coffre mis à jour.');
    }

    public function destroy(Chest $chest): RedirectResponse
    {
        if ($chest->userChests()->exists()) {
            return back()->with('error', 'Des joueurs possèdent ce coffre : désactivez-le plutôt.');
        }

        $this->deleteImage($chest->image);
        $chest->delete();

        return redirect()->route('admin.chests.index')->with('success', 'Coffre supprimé.');
    }
}
