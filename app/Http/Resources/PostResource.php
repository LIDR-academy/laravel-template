<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'published' => (bool) $this->published,
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'tags' => $this->tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'categories' => $this->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'comments_count' => $this->when(isset($this->comments_count), $this->comments_count),
            'likes_count' => $this->when(
                isset($this->likes_count) || $this->relationLoaded('likes'),
                fn () => $this->likes_count ?? $this->likes->count()
            ),
            'comments' => $this->when($this->relationLoaded('comments'), function () {
                return $this->comments->map(fn ($c) => [
                    'id' => $c->id,
                    'body' => $c->body,
                    'user' => ['id' => $c->user->id, 'name' => $c->user->name],
                ])->values();
            }),
        ];
    }
}
