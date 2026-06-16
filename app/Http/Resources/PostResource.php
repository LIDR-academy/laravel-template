<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Mirrors the legacy raw-model contract: counts and relations only appear
     * when they were eager-loaded for that endpoint (index/show/store/update),
     * so the shape matches what each route used to return.
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
            'user' => $this->whenLoaded('user'),
            'tags' => $this->whenLoaded('tags'),
            'categories' => $this->whenLoaded('categories'),
            'comments' => $this->whenLoaded('comments'),
        ];
    }
}
