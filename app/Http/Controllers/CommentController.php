<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $comments) {}

    /**
     * List a post's comments, newest first, with their authors (public).
     *
     * The original endpoint returned a flat array of comments (no `data`
     * envelope). A resource collection re-introduces that wrapper even with
     * CommentResource::$wrap = null, so we resolve it to a flat array here.
     */
    public function index(Post $post): JsonResponse
    {
        return response()->json(
            CommentResource::collection($this->comments->forPost($post))->resolve()
        );
    }

    /**
     * Create a comment on a post for the authenticated user.
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $comment = $this->comments->create($post, $request->user(), $request->validated());

        return (new CommentResource($comment))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Delete a comment owned by the authenticated user.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $this->comments->delete($comment);

        return response()->json(['message' => 'deleted']);
    }
}
