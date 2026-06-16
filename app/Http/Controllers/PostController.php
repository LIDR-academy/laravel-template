<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    /**
     * Public, paginated listing with optional filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'published' => $request->has('published') ? $request->boolean('published') : null,
            'category' => $request->input('category'),
            'tag' => $request->input('tag'),
            'q' => $request->input('q'),
        ];

        return PostResource::collection($this->posts->paginate($filters));
    }

    /**
     * Public detail view.
     */
    public function show(string $id): PostResource|JsonResponse
    {
        $post = $this->posts->findForDisplay($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return new PostResource($post);
    }

    /**
     * Create a post owned by the authenticated user.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->user(), $request->validated());

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    /**
     * Update a post — author only (enforced by PostPolicy).
     */
    public function update(UpdatePostRequest $request, string $id): PostResource|JsonResponse
    {
        $post = $this->posts->find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $this->authorize('update', $post);

        $data = $request->validated();
        if ($request->has('published')) {
            $data['published'] = $request->boolean('published');
        }

        return new PostResource($this->posts->update($post, $data));
    }

    /**
     * Delete a post — author only (enforced by PostPolicy).
     */
    public function destroy(string $id): JsonResponse
    {
        $post = $this->posts->find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $this->authorize('delete', $post);

        $this->posts->delete($post);

        return response()->json(['message' => 'deleted']);
    }
}
