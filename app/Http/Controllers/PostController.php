<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    /**
     * Public, paginated and filterable list of posts.
     */
    public function index(Request $request)
    {
        $filters = [];

        if ($request->has('published')) {
            $filters['published'] = $request->boolean('published');
        }
        if ($request->filled('category')) {
            $filters['category'] = $request->query('category');
        }
        if ($request->filled('tag')) {
            $filters['tag'] = $request->query('tag');
        }
        if ($request->filled('q')) {
            $filters['q'] = $request->query('q');
        }

        return $this->posts->list($filters)
            ->through(fn (Post $post) => PostResource::make($post));
    }

    /**
     * Public detail view of a single post.
     */
    public function show(Post $post): PostResource
    {
        return PostResource::make($this->posts->loadDetail($post));
    }

    /**
     * Create a post for the authenticated user.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->user(), $request->validated());

        return PostResource::make($post)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a post owned by the authenticated user.
     */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        return PostResource::make($this->posts->update($post, $request->validated()));
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
