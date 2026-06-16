<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeResource extends JsonResource
{
    /**
     * Transform the toggle result into an array.
     *
     * Mirrors the legacy inline contract exactly: the bare `liked` flag plus
     * the post's total `likes_count`. The underlying resource is the array
     * returned by LikeService::toggle().
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'liked' => $this->resource['liked'],
            'likes_count' => $this->resource['likes_count'],
        ];
    }
}
