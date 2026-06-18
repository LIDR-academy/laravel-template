<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * The pre-refactor endpoints returned raw comment models unwrapped (the
     * list view is a flat array, create returns the bare object). Keep that
     * contract — no top-level `data` envelope.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * Mirrors the original raw-model serialization exactly: the comment's own
     * columns plus the full author (including email_verified_at + timestamps)
     * when the `user` relation was eager-loaded.
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

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'email_verified_at' => $this->user->email_verified_at,
                'created_at' => $this->user->created_at,
                'updated_at' => $this->user->updated_at,
            ]),
        ];
    }
}
