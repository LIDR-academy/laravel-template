<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * All business logic for the Posts resource. The only place that mutates
 * post state or builds post queries.
 */
class PostService
{
    /**
     * Paginated, filtered listing for the index endpoint.
     */
    public function paginate(Request $request): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['user', 'tags', 'categories'])
            ->withCount(['comments', 'likes']);

        if ($request->has('published')) {
            $query->where('published', $request->boolean('published'));
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $request->category));
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $request->tag));
        }

        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->q.'%');
        }

        return $query->orderByDesc('id')->paginate(10);
    }

    /**
     * Single post with the relations the show endpoint exposes, or null.
     */
    public function findForShow(int|string $id): ?Post
    {
        return Post::with(['user', 'tags', 'categories', 'comments.user'])
            ->withCount('likes')
            ->find($id);
    }

    /**
     * Find a post by id, or null. Used by update/delete to drive the 404.
     */
    public function find(int|string $id): ?Post
    {
        return Post::find($id);
    }

    /**
     * Create a post authored by the given user, syncing existing pivots.
     *
     * @param  array<string, mixed>  $data
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

        if (! empty($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }
        if (! empty($data['categories'])) {
            $post->categories()->sync($data['categories']);
        }

        return $post->load(['tags', 'categories']);
    }

    /**
     * Apply a partial update, syncing pivots only when supplied.
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
