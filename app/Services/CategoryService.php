<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * All categories ordered alphabetically by name.
     *
     * @return Collection<int, Category>
     */
    public function list(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * Persist a new category, deriving its slug from the name.
     *
     * @param  array{name: string, description?: string|null}  $data
     */
    public function create(array $data): Category
    {
        $category = new Category;
        $category->name = $data['name'];
        $category->slug = Str::slug($data['name']);
        $category->description = $data['description'] ?? null;
        $category->save();

        return $category;
    }

    /**
     * Apply a partial update to a category, regenerating its slug when the name changes.
     *
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function update(Category $category, array $data): Category
    {
        if (array_key_exists('name', $data)) {
            $category->name = $data['name'];
            $category->slug = Str::slug($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $category->description = $data['description'];
        }

        $category->save();

        return $category;
    }

    /**
     * Delete a category (detaches from posts via the pivot FK).
     */
    public function delete(Category $category): void
    {
        $category->delete();
    }
}
