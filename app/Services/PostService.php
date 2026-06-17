<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostService
{
    public function list(Request $request): LengthAwarePaginator
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

    public function findOrFail(int $id): Post
    {
        return Post::with(['user', 'tags', 'categories', 'comments.user'])
            ->withCount('likes')
            ->findOrFail($id);
    }

    public function create(array $data, User $author): Post
    {
        $post = Post::create([
            'user_id' => $author->id,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'body' => $data['body'],
            'published' => $data['published'] ?? false,
        ]);

        if (! empty($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        if (! empty($data['categories'])) {
            $post->categories()->sync($data['categories']);
        }

        return $post->load(['user', 'tags', 'categories']);
    }

    public function update(Post $post, array $data): Post
    {
        $post->fill(array_filter([
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'published' => $data['published'] ?? null,
        ], fn ($v) => $v !== null));

        $post->save();

        if (array_key_exists('tags', $data)) {
            $post->tags()->sync($data['tags'] ?? []);
        }

        if (array_key_exists('categories', $data)) {
            $post->categories()->sync($data['categories'] ?? []);
        }

        return $post->load(['user', 'tags', 'categories']);
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }
}
