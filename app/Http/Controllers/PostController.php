<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostDetailResource;
use App\Http\Resources\PostListResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function __construct(private PostService $postService) {}

    public function index(Request $request)
    {
        $query = Post::query()->with(['user', 'tags', 'categories'])->withCount(['comments', 'likes']);

        if ($request->has('published')) {
            $query->where('published', $request->boolean('published'));
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tag);
            });
        }

        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->q.'%');
        }

        $posts = $query->orderByDesc('id')->paginate(10);

        return response()->json([
            'data' => PostListResource::collection($posts->getCollection())->resolve(),
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'total' => $posts->total(),
            'per_page' => $posts->perPage(),
        ]);
    }

    public function store(StorePostRequest $request)
    {
        $post = $this->postService->createPost($request->user(), $request->validated());

        return response()->json(
            (new PostResource($post->load(['tags', 'categories'])))->toArray($request),
            201
        );
    }

    public function show(string $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $loaded = $post->load(['user', 'tags', 'categories', 'comments.user'])->loadCount('likes');

        return response()->json(
            (new PostDetailResource($loaded))->toArray(request())
        );
    }

    public function update(UpdatePostRequest $request, string $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post = $this->postService->updatePost($post, $request->validated());

        return response()->json(
            (new PostResource($post->load(['tags', 'categories'])))->toArray($request)
        );
    }

    public function destroy(string $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $user = Auth::user();
        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->postService->deletePost($post);

        return response()->json(['message' => 'deleted']);
    }
}
