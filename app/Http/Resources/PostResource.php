<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * The endpoints return posts unwrapped (no top-level "data" envelope),
     * preserving the original raw-model contract the API exposed.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * Relations and counts are rendered only when the controller eager
     * loaded / counted them, so a single Resource serves both the list view
     * (counts, no comments) and the detail view (comments, likes count)
     * identically to the previous inline responses.
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

            'user' => $this->whenLoaded('user'),
            'tags' => $this->whenLoaded('tags'),
            'categories' => $this->whenLoaded('categories'),
            'comments' => $this->whenLoaded('comments'),

            'comments_count' => $this->whenCounted('comments'),
            'likes_count' => $this->whenCounted('likes'),
        ];
    }
}
