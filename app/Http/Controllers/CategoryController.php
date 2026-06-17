<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    /**
     * Public list of all categories, ordered by name.
     */
    public function index(): CategoryCollection
    {
        return new CategoryCollection($this->categories->list());
    }

    /**
     * Public detail view of a single category.
     */
    public function show(Category $category): CategoryResource
    {
        return CategoryResource::make($category);
    }

    /**
     * Create a category for the authenticated user.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->validated());

        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        return CategoryResource::make($this->categories->update($category, $request->validated()));
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->categories->delete($category);

        return response()->json(['message' => 'deleted']);
    }
}
