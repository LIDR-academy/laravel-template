<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TagService
{
    /**
     * All tags ordered alphabetically by name.
     *
     * @return Collection<int, Tag>
     */
    public function list(): Collection
    {
        return Tag::query()->orderBy('name')->get();
    }

    /**
     * Persist a new tag, deriving its slug from the name.
     *
     * @param  array{name: string}  $data
     */
    public function create(array $data): Tag
    {
        $tag = new Tag;
        $tag->name = $data['name'];
        $tag->slug = Str::slug($data['name']);
        $tag->save();

        return $tag;
    }

    /**
     * Apply a partial update to a tag, regenerating its slug when the name changes.
     *
     * @param  array{name?: string}  $data
     */
    public function update(Tag $tag, array $data): Tag
    {
        if (array_key_exists('name', $data)) {
            $tag->name = $data['name'];
            $tag->slug = Str::slug($data['name']);
        }

        $tag->save();

        return $tag;
    }

    /**
     * Delete a tag (detaches from posts via the pivot FK).
     */
    public function delete(Tag $tag): void
    {
        $tag->delete();
    }
}
