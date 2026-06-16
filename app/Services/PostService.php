<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PostService
{
    /**
     * Paginated, filtered listing for the public index.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['user', 'tags', 'categories'])
            ->withCount(['comments', 'likes']);

        // `published` is applied whenever the key is present (true or false).
        if (array_key_exists('published', $filters) && ! is_null($filters['published'])) {
            $query->where('published', $filters['published']);
        }

        if (filled($filters['category'] ?? null)) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category']);
            });
        }

        if (filled($filters['tag'] ?? null)) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('tags.id', $filters['tag']);
            });
        }

        if (filled($filters['q'] ?? null)) {
            $query->where('title', 'like', '%'.$filters['q'].'%');
        }

        return $query->orderByDesc('id')->paginate(10);
    }

    /**
     * Load a single post with the relations the detail view exposes.
     */
    public function findForDisplay(int|string $id): ?Post
    {
        return Post::with(['user', 'tags', 'categories', 'comments.user'])
            ->withCount('likes')
            ->find($id);
    }

    /**
     * Bare lookup used for ownership checks on write operations.
     */
    public function find(int|string $id): ?Post
    {
        return Post::find($id);
    }

    /**
     * Create a post for the given author and sync its pivots.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Post
    {
        $post = new Post;
        $post->user_id = $author->id;
        $post->title = $data['title'];
        $post->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $post->body = $data['body'];
        $post->published = $data['published'] ?? false;
        $post->save();

        if (array_key_exists('tags', $data)) {
            $post->tags()->sync($data['tags'] ?? []);
        }
        if (array_key_exists('categories', $data)) {
            $post->categories()->sync($data['categories'] ?? []);
        }

        return $post->load(['tags', 'categories']);
    }

    /**
     * Apply a partial update to a post and sync its pivots.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, array $data): Post
    {
        if (array_key_exists('title', $data)) {
            $post->title = $data['title'];
        }
        if (array_key_exists('body', $data)) {
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

        return $post->load(['tags', 'categories']);
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }
}
