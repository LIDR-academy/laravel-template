<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeResource extends JsonResource
{
    /**
     * The toggle result is returned unwrapped (no top-level "data"
     * envelope), preserving the original raw-array contract the API
     * exposed: { liked, likes_count }.
     */
    public static $wrap = null;

    /**
     * Transform the toggle result into an array.
     *
     * Wraps the LikeService result array, casting to the stable types the
     * endpoint has always returned (boolean flag, integer count).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'liked' => (bool) $this->resource['liked'],
            'likes_count' => (int) $this->resource['likes_count'],
        ];
    }
}
