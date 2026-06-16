<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TagCollection extends ResourceCollection
{
    /**
     * Each item is shaped by TagResource.
     */
    public $collects = TagResource::class;

    /**
     * The list is returned unwrapped (no top-level "data" envelope),
     * preserving the original raw-collection contract GET /api/tags exposed.
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
