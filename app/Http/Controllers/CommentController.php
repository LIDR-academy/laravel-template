<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Services\CommentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly CommentService $comments) {}

    /**
     * GET /api/posts/{id}/comments — public listing of a post's comments.
     */
    public function index(string $id): AnonymousResourceCollection
    {
        $post = $this->comments->findPost($id);
        abort_if($post === null, 404, 'Post not found');

        return CommentResource::collection($this->comments->forPost($post));
    }

    /**
     * POST /api/posts/{id}/comments — create a comment for the authenticated user.
     */
    public function store(StoreCommentRequest $request, string $id): JsonResponse
    {
        $post = $this->comments->findPost($id);
        abort_if($post === null, 404, 'Post not found');

        $comment = $this->comments->create($request->user(), $post, $request->validated());

        return CommentResource::make($comment)->response()->setStatusCode(201);
    }

    /**
     * DELETE /api/comments/{id} — author-only delete.
     */
    public function destroy(string $id): JsonResponse
    {
        $comment = $this->comments->find($id);
        abort_if($comment === null, 404, 'Comment not found');

        $this->authorize('delete', $comment);

        $this->comments->delete($comment);

        return response()->json(['message' => 'deleted']);
    }
}
