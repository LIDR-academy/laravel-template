<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Any authenticated user may comment.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the author may delete their comment.
     *
     * Denies with the literal "Forbidden" message the API has always
     * returned for this case (rendered as a 403 JSON body).
     */
    public function delete(User $user, Comment $comment): Response
    {
        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::deny('Forbidden');
    }
}
