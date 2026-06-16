<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;

/**
 * All business logic for the Likes resource. The only place that mutates
 * like state or builds like queries.
 */
class LikeService
{
    /**
     * Find a post by id, or null. Drives the 404 on the toggle endpoint,
     * which is scoped to a post.
     */
    public function findPost(int|string $id): ?Post
    {
        return Post::find($id);
    }

    /**
     * Toggle the given user's like on the given post: remove it if it already
     * exists, otherwise create it. Returns the response contract — whether the
     * post is now liked by the user and the post's total like count.
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
