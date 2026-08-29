<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\CategoryRequest;
use App\Http\Resources\Api\V1\Admin\AdminCategoryResource;
use App\Models\Category;
use App\Services\Admin\AdminContentService;
use Illuminate\Http\Request;
use Throwable;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AdminContentService $content,
    ) {}

    public function index(Request $request)
    {
        $categories = Category::query()
            ->withCount('questions')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->get();

        return $this->successResponse(AdminCategoryResource::collection($categories));
    }

    public function store(CategoryRequest $request)
    {
        try {
            $data = $request->validated();
            $data['slug'] = $data['slug'] ?? $this->content->uniqueSlug('categories', $data['name']);

            $category = Category::query()->create($data);

            return $this->successResponse(new AdminCategoryResource($category), 'Catégorie créée.', 201);
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin category store failed');
        }
    }

    public function update(CategoryRequest $request, Category $category)
    {
        try {
            $category->update($request->validated());

            return $this->successResponse(new AdminCategoryResource($category->fresh()), 'Catégorie mise à jour.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin category update failed');
        }
    }

    /**
     * Categories have no soft deletes and questions reference them with
     * nullOnDelete, so removing one silently unclassifies its questions.
     * Deactivating keeps that classification intact.
     */
    public function destroy(Category $category)
    {
        try {
            if ($category->questions()->exists()) {
                $category->update(['is_active' => false]);

                return $this->successResponse(
                    new AdminCategoryResource($category->fresh()),
                    'Catégorie désactivée : elle est encore utilisée par des questions.'
                );
            }

            $category->delete();

            return $this->successResponse(null, 'Catégorie supprimée.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin category destroy failed');
        }
    }
}
