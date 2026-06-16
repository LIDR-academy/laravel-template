<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Only the author may delete the comment.
     *
     * Denies with the legacy "Forbidden" message so the 403 response body
     * stays identical to the old inline check.
     */
    public function delete(User $user, Comment $comment): Response
    {
        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::deny('Forbidden');
    }
}
