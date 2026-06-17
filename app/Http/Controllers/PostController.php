<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(private PostService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return PostResource::collection($this->service->list($request));
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->service->create($request->validated(), $request->user());

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function show(int $id): PostResource|JsonResponse
    {
        $post = Post::with(['user', 'tags', 'categories', 'comments.user'])
            ->withCount('likes')
            ->find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return new PostResource($post);
    }

    public function update(UpdatePostRequest $request, int $id): PostResource|JsonResponse
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        Gate::authorize('update', $post);

        return new PostResource($this->service->update($post, $request->validated()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        Gate::authorize('delete', $post);

        $this->service->delete($post);

        return response()->json(['message' => 'deleted']);
    }
}
