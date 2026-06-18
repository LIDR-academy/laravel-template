<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
    /**
     * A post's comments with their authors, newest first.
     *
     * @return Collection<int, Comment>
     */
    public function forPost(Post $post): Collection
    {
        return $post->comments()->with('user')->orderByDesc('id')->get();
    }

    /**
     * Create a comment on the given post for the given author.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Post $post, User $author, array $data): Comment
    {
        $comment = new Comment;
        $comment->post_id = $post->id;
        $comment->user_id = $author->id;
        $comment->body = $data['body'];
        $comment->save();

        return $comment->load('user');
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
