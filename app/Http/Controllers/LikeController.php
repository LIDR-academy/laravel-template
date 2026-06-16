<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLikeRequest;
use App\Http\Resources\LikeResource;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function __construct(private readonly LikeService $likes) {}

    /**
     * POST /api/posts/{id}/like — toggle the authenticated user's like on a
     * post. Any authenticated user may like; there is no ownership policy.
     */
    public function store(StoreLikeRequest $request, string $id): JsonResponse
    {
        $post = $this->likes->findPost($id);
        abort_if($post === null, 404, 'Post not found');

        $result = $this->likes->toggle($request->user(), $post);

        return LikeResource::make($result)->response();
    }
}
