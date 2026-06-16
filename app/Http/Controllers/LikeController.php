<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleLikeRequest;
use App\Http\Resources\LikeResource;
use App\Models\Post;
use App\Services\LikeService;

class LikeController extends Controller
{
    public function __construct(private readonly LikeService $likes) {}

    /**
     * Toggle the authenticated user's like on a post.
     */
    public function toggle(ToggleLikeRequest $request, Post $post): LikeResource
    {
        return LikeResource::make(
            $this->likes->toggle($request->user(), $post)
        );
    }
}
