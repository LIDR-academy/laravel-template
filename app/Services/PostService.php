<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PostService
{
    /**
     * Paginated list of posts with authors, taxonomy and engagement counts.
     *
     * @param  array{published?: bool, category?: int|string, tag?: int|string, q?: string}  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['user', 'tags', 'categories'])
            ->withCount(['comments', 'likes']);

        if (array_key_exists('published', $filters)) {
            $query->where('published', $filters['published']);
        }

        if (array_key_exists('category', $filters)) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $filters['category']));
        }

        if (array_key_exists('tag', $filters)) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $filters['tag']));
        }

        if (array_key_exists('q', $filters)) {
            $query->where('title', 'like', '%'.$filters['q'].'%');
        }

        return $query->orderByDesc('id')->paginate(10);
    }

    /**
     * Eager load everything needed to render a single post in detail.
     */
    public function loadDetail(Post $post): Post
    {
        return $post
            ->load(['user', 'tags', 'categories', 'comments.user'])
            ->loadCount('likes');
    }

    /**
     * Persist a new post for the given author and sync its taxonomy.
     *
     * @param  array{title: string, body: string, published?: bool, tags?: int[], categories?: int[]}  $data
     */
    public function create(User $user, array $data): Post
    {
        $post = new Post;
        $post->user_id = $user->id;
        $post->title = $data['title'];
        $post->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $post->body = $data['body'];
        $post->published = $data['published'] ?? false;
        $post->save();

        if (array_key_exists('tags', $data)) {
            $post->tags()->sync($data['tags']);
        }

        if (array_key_exists('categories', $data)) {
            $post->categories()->sync($data['categories']);
        }

        return $post->load(['tags', 'categories']);
    }

    /**
     * Apply a partial update to an existing post and sync its taxonomy.
     *
     * @param  array{title?: string, body?: string, published?: bool, tags?: int[], categories?: int[]}  $data
     */
    public function update(Post $post, array $data): Post
    {
        $post->fill(array_intersect_key($data, array_flip(['title', 'body', 'published'])));
        $post->save();

        if (array_key_exists('tags', $data)) {
            $post->tags()->sync($data['tags']);
        }

        if (array_key_exists('categories', $data)) {
            $post->categories()->sync($data['categories']);
        }

        return $post->load(['tags', 'categories']);
    }

    /**
     * Delete a post (cascades to comments, likes and pivots via FK).
     */
    public function delete(Post $post): void
    {
        $post->delete();
    }
}
