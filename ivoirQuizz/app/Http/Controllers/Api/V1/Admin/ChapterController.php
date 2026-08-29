<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ChapterRequest;
use App\Http\Requests\Api\V1\Admin\ReorderRequest;
use App\Http\Resources\Api\V1\Admin\AdminChapterResource;
use App\Models\Chapter;
use App\Services\Admin\AdminContentService;
use Illuminate\Http\Request;
use Throwable;

class ChapterController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AdminContentService $content,
    ) {}

    public function index(Request $request)
    {
        $chapters = Chapter::query()
            ->with('region')
            ->withCount('levels')
            ->when($request->filled('region_id'), fn ($q) => $q->where('region_id', $request->integer('region_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('region_id')
            ->orderBy('order')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return AdminChapterResource::collection($chapters)->response();
    }

    public function store(ChapterRequest $request)
    {
        try {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? $this->content->uniqueSlug(
                'chapters',
                $data['name'],
                null,
                ['region_id' => $data['region_id']],
            );

            $chapter = Chapter::query()->create($data);
            $this->content->forgetPlayerMapCache();

            return $this->successResponse(new AdminChapterResource($chapter->load('region')), 'Chapitre créé.', 201);
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin chapter store failed');
        }
    }

    public function show(Chapter $chapter)
    {
        $chapter->load(['region', 'levels' => fn ($q) => $q->withCount(['questions as active_questions_count' => fn ($sub) => $sub->where('is_active', true)])->orderBy('order')]);

        return $this->successResponse(new AdminChapterResource($chapter));
    }

    public function update(ChapterRequest $request, Chapter $chapter)
    {
        try {
            $chapter->update($request->validated());
            $this->content->forgetPlayerMapCache();

            return $this->successResponse(new AdminChapterResource($chapter->fresh('region')), 'Chapitre mis à jour.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin chapter update failed');
        }
    }

    public function destroy(Chapter $chapter)
    {
        try {
            $chapter->delete();
            $this->content->forgetPlayerMapCache();

            return $this->successResponse(null, 'Chapitre archivé.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin chapter destroy failed');
        }
    }

    public function reorder(ReorderRequest $request)
    {
        try {
            $updated = $this->content->applyOrder(Chapter::class, $request->orderedIds());

            return $this->successResponse(['updated' => $updated], 'Ordre des chapitres mis à jour.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin chapter reorder failed');
        }
    }
}
