<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\LevelRequest;
use App\Http\Requests\Api\V1\Admin\ReorderRequest;
use App\Http\Resources\Api\V1\Admin\AdminLevelResource;
use App\Models\Level;
use App\Services\Admin\AdminContentService;
use Illuminate\Http\Request;
use Throwable;

class LevelController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AdminContentService $content,
    ) {}

    public function index(Request $request)
    {
        $levels = Level::query()
            ->with('chapter.region')
            ->withCount(['questions as active_questions_count' => fn ($q) => $q->where('is_active', true)])
            ->when($request->filled('chapter_id'), fn ($q) => $q->where('chapter_id', $request->integer('chapter_id')))
            ->when($request->filled('region_id'), fn ($q) => $q->whereHas('chapter', fn ($sub) => $sub->where('region_id', $request->integer('region_id'))))
            ->when($request->filled('node_type'), fn ($q) => $q->where('node_type', $request->string('node_type')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            // The back office's most useful filter: levels that cannot start a
            // session because they own fewer questions than they draw.
            ->when($request->boolean('incomplete_only'), fn ($q) => $q->missingQuestions())
            ->orderBy('chapter_id')
            ->orderBy('order')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return AdminLevelResource::collection($levels)->response();
    }

    public function store(LevelRequest $request)
    {
        try {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? $this->content->uniqueSlug(
                'levels',
                $data['title'],
                null,
                ['chapter_id' => $data['chapter_id']],
            );

            $level = Level::query()->create($data);
            $this->content->forgetPlayerMapCache();

            return $this->successResponse(new AdminLevelResource($level->load('chapter.region')), 'Niveau créé.', 201);
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin level store failed');
        }
    }

    public function show(Level $level)
    {
        $level->load('chapter.region')
            ->loadCount(['questions as active_questions_count' => fn ($q) => $q->where('is_active', true)]);

        return $this->successResponse(new AdminLevelResource($level));
    }

    public function update(LevelRequest $request, Level $level)
    {
        try {
            $level->update($request->validated());
            $this->content->forgetPlayerMapCache();

            $level->load('chapter.region')
                ->loadCount(['questions as active_questions_count' => fn ($q) => $q->where('is_active', true)]);

            return $this->successResponse(new AdminLevelResource($level), 'Niveau mis à jour.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin level update failed');
        }
    }

    public function destroy(Level $level)
    {
        try {
            $level->delete();
            $this->content->forgetPlayerMapCache();

            return $this->successResponse(null, 'Niveau archivé.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin level destroy failed');
        }
    }

    public function reorder(ReorderRequest $request)
    {
        try {
            $updated = $this->content->applyOrder(Level::class, $request->orderedIds());

            return $this->successResponse(['updated' => $updated], 'Ordre des niveaux mis à jour.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin level reorder failed');
        }
    }
}
