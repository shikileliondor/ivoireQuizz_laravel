<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Return all active categories with active questions count.
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->withCount([
                'questions as questions_count' => fn ($query) => $query->active(),
            ])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Catégories actives récupérées.',
        ]);
    }
}
