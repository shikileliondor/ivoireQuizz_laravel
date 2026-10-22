<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Return all active categories with active questions count.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->trim().'%'))
            ->withCount([
                'questions as questions_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->when($request->boolean('has_questions'), fn ($query) => $query->has('questions'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Catégories actives récupérées.',
        ]);
    }
}
