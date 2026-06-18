<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    /**
     * Paginated, filterable list of posts (public).
     */
    public function index(Request $request): LengthAwarePaginator
    {
        return $this->posts->paginate([
            'published' => $request->has('published') ? $request->boolean('published') : null,
            'category' => $request->filled('category') ? $request->integer('category') : null,
            'tag' => $request->filled('tag') ? $request->integer('tag') : null,
            'q' => $request->filled('q') ? (string) $request->query('q') : null,
        ])->through(fn (Post $post) => new PostResource($post));
    }

    /**
     * A single post with author, taxonomy, comments and like count (public).
     */
    public function show(Post $post): PostResource
    {
        return new PostResource($this->posts->loadDetail($post));
    }

    /**
     * Create a post for the authenticated user.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->user(), $request->validated());

        return (new PostResource($post))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a post owned by the authenticated user.
     */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        return new PostResource($this->posts->update($post, $request->validated()));
    }

    /**
     * Delete a post owned by the authenticated user.
     */
    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $this->posts->delete($post);

        return response()->json(['message' => 'deleted']);
    }
}
