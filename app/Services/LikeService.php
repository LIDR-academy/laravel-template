<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;

class LikeService
{
    /**
     * Toggle the given user's like on the given post.
     *
     * Removes the like if it already exists, otherwise creates it, then
     * reports the resulting state and the post's total like count.
     *
     * @return array{liked: bool, likes_count: int}
     */
    public function toggle(User $user, Post $post): array
    {
        $existing = Like::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);
            $liked = true;
        }

        return [
            'liked' => $liked,
            'likes_count' => Like::where('post_id', $post->id)->count(),
        ];
    }
}
