<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Only the author may update their own post.
     *
     * Consolidates the previously duplicated inline ownership checks
     * (`$post->user_id !== $request->user()->id`) from routes/api.php.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Only the author may delete their own post.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
