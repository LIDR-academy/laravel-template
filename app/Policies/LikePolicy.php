<?php

namespace App\Policies;

use App\Models\User;

class LikePolicy
{
    /**
     * Any authenticated user may toggle a like on a post.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
