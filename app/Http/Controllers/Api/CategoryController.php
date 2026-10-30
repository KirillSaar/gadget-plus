<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories)
    {
    }

    public function index(Request $request)
    {
        return CategoryResource::collection(
            $this->categories->paginate(min($request->integer('per_page', 10), 50))
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        return new CategoryResource(
            $this->categories->update($category, $request->validated())
        );
    }

    public function destroy(Category $category): Response|JsonResponse
    {
        try {
            $this->categories->delete($category);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Нельзя удалить категорию: в ней есть товары.',
            ], 409);
        }

        return response()->noContent();
    }
}