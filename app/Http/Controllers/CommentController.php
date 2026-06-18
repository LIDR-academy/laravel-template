<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $comments) {}

    /**
     * Public, newest-first list of a post's comments.
     */
    public function index(Post $post): CommentCollection
    {
        return new CommentCollection($this->comments->listForPost($post));
    }

    /**
     * Create a comment on a post for the authenticated user.
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $comment = $this->comments->create($request->user(), $post, $request->validated());

        return CommentResource::make($comment)
            ->response()
            ->setStatusCode(201);
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
