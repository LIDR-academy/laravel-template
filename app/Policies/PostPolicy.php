<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    /**
     * Only the author may update the post.
     */
    public function update(User $user, Post $post): Response
    {
        return $this->owns($user, $post);
    }

    /**
     * Only the author may delete the post.
     */
    public function delete(User $user, Post $post): Response
    {
        return $this->owns($user, $post);
    }

    /**
     * Shared ownership gate. Denies with the legacy "Forbidden" message so the
     * 403 response body stays identical to the old inline checks.
     */
    protected function owns(User $user, Post $post): Response
    {
        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('Forbidden');
    }
}
