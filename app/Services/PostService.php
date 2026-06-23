<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class PostService
{
    public function createPost(User $user, array $data): Post
    {
        $post = new Post;
        $post->user_id = $user->id;
        $post->title = $data['title'];
        $post->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $post->body = $data['body'];
        $post->published = $data['published'] ?? false;
        $post->save();

        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }
        if (isset($data['categories'])) {
            $post->categories()->sync($data['categories']);
        }

        return $post;
    }

    public function updatePost(Post $post, array $data): Post
    {
        if (isset($data['title'])) {
            $post->title = $data['title'];
        }
        if (isset($data['body'])) {
            $post->body = $data['body'];
        }
        if (array_key_exists('published', $data)) {
            $post->published = $data['published'];
        }
        $post->save();

        if (array_key_exists('tags', $data)) {
            $post->tags()->sync($data['tags'] ?? []);
        }
        if (array_key_exists('categories', $data)) {
            $post->categories()->sync($data['categories'] ?? []);
        }

        return $post;
    }

    public function deletePost(Post $post): void
    {
        $post->delete();
    }
}
