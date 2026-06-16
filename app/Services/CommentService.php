<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
    /**
     * A post's comments with their authors, newest-first.
     *
     * @return Collection<int, Comment>
     */
    public function listForPost(Post $post): Collection
    {
        return $post->comments()
            ->with('user')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Persist a new comment authored by the given user on the given post.
     *
     * @param  array{body: string}  $data
     */
    public function create(User $user, Post $post, array $data): Comment
    {
        $comment = new Comment;
        $comment->post_id = $post->id;
        $comment->user_id = $user->id;
        $comment->body = $data['body'];
        $comment->save();

        return $comment->load('user');
    }

    /**
     * Delete a comment.
     */
    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
