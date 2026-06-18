<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Comments are returned unwrapped (no top-level "data" envelope),
     * preserving the original raw-model contract the API exposed.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * The author is rendered only when the controller eager loaded it,
     * matching the previous inline `$comment->load('user')` responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'body' => $this->body,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user' => $this->whenLoaded('user'),
        ];
    }
}
