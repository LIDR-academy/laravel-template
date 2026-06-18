<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * The existing API returns posts unwrapped (no top-level `data` envelope);
     * the list view supplies its own paginator envelope. Keep that contract.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * Relations and counts appear only when the controller eager-loaded them,
     * so a single Resource serves the list (counts, no comments), the detail
     * (comments + likes count) and the create/update views (tags + categories).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'published' => (bool) $this->published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'comments_count' => $this->whenCounted('comments'),
            'likes_count' => $this->whenCounted('likes'),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),

            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])),

            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])),

            'comments' => $this->whenLoaded('comments', fn () => $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => $comment->relationLoaded('user') && $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email,
                ] : null,
            ])),
        ];
    }
}
