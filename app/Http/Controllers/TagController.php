<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagCollection;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(private readonly TagService $tags) {}

    /**
     * Public list of all tags, ordered by name.
     */
    public function index(): TagCollection
    {
        return new TagCollection($this->tags->list());
    }

    /**
     * Public detail view of a single tag.
     */
    public function show(Tag $tag): TagResource
    {
        return TagResource::make($tag);
    }

    /**
     * Create a tag for the authenticated user.
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $this->tags->create($request->validated());

        return TagResource::make($tag)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing tag.
     */
    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        return TagResource::make($this->tags->update($tag, $request->validated()));
    }

    /**
     * Delete a tag.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $this->tags->delete($tag);

        return response()->json(['message' => 'deleted']);
    }
}
