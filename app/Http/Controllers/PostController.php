<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Services\PostService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly PostService $posts) {}

    /**
     * GET /api/posts — public, paginated & filterable listing.
     *
     * Keeps the legacy paginator envelope by shaping each item through
     * PostResource while returning the paginator itself.
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $posts = $this->posts->paginate($request);

        $posts->setCollection(
            $posts->getCollection()->map(
                fn ($post) => (new PostResource($post))->resolve($request)
            )
        );

        return $posts;
    }

    /**
     * POST /api/posts — create a post for the authenticated user.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->user(), $request->validated());

        return PostResource::make($post)->response()->setStatusCode(201);
    }

    /**
     * GET /api/posts/{id} — public, single post with relations & comments.
     */
    public function show(string $id): PostResource
    {
        $post = $this->posts->findForShow($id);
        abort_if($post === null, 404, 'Post not found');

        return PostResource::make($post);
    }

    /**
     * PUT /api/posts/{id} — author-only partial update.
     */
    public function update(UpdatePostRequest $request, string $id): PostResource
    {
        $post = $this->posts->find($id);
        abort_if($post === null, 404, 'Post not found');

        $this->authorize('update', $post);

        return PostResource::make($this->posts->update($post, $request->validated()));
    }

    /**
     * DELETE /api/posts/{id} — author-only delete.
     */
    public function destroy(string $id): JsonResponse
    {
        $post = $this->posts->find($id);
        abort_if($post === null, 404, 'Post not found');

        $this->authorize('delete', $post);

        $this->posts->delete($post);

        return response()->json(['message' => 'deleted']);
    }
}
