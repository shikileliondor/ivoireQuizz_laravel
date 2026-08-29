<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\RegionRequest;
use App\Http\Requests\Api\V1\Admin\ReorderRequest;
use App\Http\Resources\Api\V1\Admin\AdminRegionResource;
use App\Models\Region;
use App\Services\Admin\AdminContentService;
use Illuminate\Http\Request;
use Throwable;

class RegionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AdminContentService $content,
    ) {}

    public function index(Request $request)
    {
        $regions = Region::query()
            ->withCount(['chapters', 'levels'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('order')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return AdminRegionResource::collection($regions)->response();
    }

    public function store(RegionRequest $request)
    {
        try {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? $this->content->uniqueSlug('regions', $data['name']);

            $region = Region::query()->create($data);
            $this->content->forgetPlayerMapCache();

            return $this->successResponse(new AdminRegionResource($region), 'Région créée.', 201);
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin region store failed');
        }
    }

    public function show(Region $region)
    {
        $region->loadCount(['chapters', 'levels'])
            ->load(['chapters' => fn ($q) => $q->withCount('levels')->orderBy('order')]);

        return $this->successResponse(new AdminRegionResource($region));
    }

    public function update(RegionRequest $request, Region $region)
    {
        try {
            $region->update($request->validated());
            $this->content->forgetPlayerMapCache();

            return $this->successResponse(new AdminRegionResource($region->fresh()), 'Région mise à jour.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin region update failed');
        }
    }

    /**
     * Regions are soft-deleted: player progress rows point at them, and a hard
     * delete would cascade away the history of everyone who played there.
     */
    public function destroy(Region $region)
    {
        try {
            $region->delete();
            $this->content->forgetPlayerMapCache();

            return $this->successResponse(null, 'Région archivée.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin region destroy failed');
        }
    }

    public function reorder(ReorderRequest $request)
    {
        try {
            $updated = $this->content->applyOrder(Region::class, $request->orderedIds());

            return $this->successResponse(['updated' => $updated], 'Ordre des régions mis à jour.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin region reorder failed');
        }
    }
}
