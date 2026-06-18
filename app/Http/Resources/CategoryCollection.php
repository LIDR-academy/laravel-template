<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    /**
     * Each item is shaped by CategoryResource.
     */
    public $collects = CategoryResource::class;

    /**
     * The list is returned unwrapped (no top-level "data" envelope),
     * preserving the original raw-collection contract GET /api/categories
     * exposed.
     */
    public static $wrap = null;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->all();
    }
}
