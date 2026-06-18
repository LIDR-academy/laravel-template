<?php

namespace App\Http\Controllers;

use App\Http\Resources\LikeResource;
use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(private readonly LikeService $likes) {}

    /**
     * Toggle the authenticated user's like on the given post.
     */
    public function toggle(Request $request, Post $post): JsonResponse
    {
        return (new LikeResource($this->likes->toggle($post, $request->user())))->response();
    }
}
