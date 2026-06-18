<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PostService
{
    /**
     * Paginated list of posts with relations + counts, newest first.
     *
     * @param  array{published?: bool|null, category?: int|null, tag?: int|null, q?: string|null}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['user', 'tags', 'categories'])
            ->withCount(['comments', 'likes']);

        if (! is_null($filters['published'] ?? null)) {
            $query->where('published', $filters['published']);
        }

        if (! is_null($filters['category'] ?? null)) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $filters['category']));
        }

        if (! is_null($filters['tag'] ?? null)) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $filters['tag']));
        }

        if (! is_null($filters['q'] ?? null)) {
            $query->where('title', 'like', '%'.$filters['q'].'%');
        }

        return $query->orderByDesc('id')->paginate(10);
    }

    /**
     * Eager-load the single-post detail view (author, taxonomy, comment authors, like count).
     */
    public function loadDetail(Post $post): Post
    {
        return $post->load(['user', 'tags', 'categories', 'comments.user'])->loadCount('likes');
    }

    /**
     * Create a post for the given author and sync its taxonomy.
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

        $post->tags()->sync($data['tags'] ?? []);
        $post->categories()->sync($data['categories'] ?? []);

        return $post->load(['tags', 'categories']);
    }

    /**
     * Apply a partial update and re-sync any taxonomy that was provided.
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
            $post->tags()->sync($data['tags']);
        }
        if (array_key_exists('categories', $data)) {
            $post->categories()->sync($data['categories']);
        }

        return $post->load(['tags', 'categories']);
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }
}
