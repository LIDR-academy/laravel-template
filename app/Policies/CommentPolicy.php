<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Only the author may delete their comment.
     *
     * Returns a denial carrying the literal "Forbidden" message so the 403
     * response keeps the pre-refactor contract (`{"message":"Forbidden"}`).
     */
    public function delete(User $user, Comment $comment): Response
    {
        return $comment->user_id === $user->id
            ? Response::allow()
            : Response::deny('Forbidden');
    }
}
