<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * All business logic for the Comments resource. The only place that mutates
 * comment state or builds comment queries.
 */
class CommentService
{
    /**
     * Find a post by id, or null. Drives the 404 on the comment endpoints
     * that are scoped to a post (index/store).
     */
    public function findPost(int|string $id): ?Post
    {
        return Post::find($id);
    }

    /**
     * Comments for a post, newest first, with their author eager-loaded —
     * the shape the index endpoint exposes.
     *
     * @return Collection<int, Comment>
     */
    public function forPost(Post $post): Collection
    {
        return $post->comments()->with('user')->orderByDesc('id')->get();
    }

    /**
     * Find a comment by id, or null. Used by destroy to drive the 404.
     */
    public function find(int|string $id): ?Comment
    {
        return Comment::find($id);
    }

    /**
     * Create a comment authored by the given user on the given post,
     * returning it with the author loaded for the response.
     *
     * @param  array<string, mixed>  $data
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

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
