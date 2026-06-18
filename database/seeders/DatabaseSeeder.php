<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $test = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $users = User::factory(4)->create()->push($test);

        $categories = Category::factory(5)->create();
        $tags = Tag::factory(10)->create();

        Post::factory(8)->make()->each(function (Post $post) use ($users, $categories, $tags) {
            $post->user_id = $users->random()->id;
            $post->save();

            // attach 1-3 categories and 2-4 tags via pivots
            $post->categories()->attach($categories->random(rand(1, 3))->pluck('id')->toArray());
            $post->tags()->attach($tags->random(rand(2, 4))->pluck('id')->toArray());

            // a handful of comments per post
            Comment::factory(rand(2, 6))->create([
                'post_id' => $post->id,
                'user_id' => $users->random()->id,
            ]);

            // some likes (avoid duplicate user/post pairs)
            foreach ($users->random(rand(1, $users->count())) as $u) {
                Like::firstOrCreate([
                    'post_id' => $post->id,
                    'user_id' => $u->id,
                ]);
            }
        });
    }
}
