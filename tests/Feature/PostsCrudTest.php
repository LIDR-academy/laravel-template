<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ============================================================================
// GET /api/posts — List Posts (public, paginated, filterable)
// ============================================================================

test('GET /posts returns paginated posts with full structure', function () {
    // Arrange
    $user = User::factory()->create();
    Post::factory(15)->create(['user_id' => $user->id, 'published' => true]);

    // Act
    $response = $this->getJson('/api/posts');

    // Assert
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'user_id',
                'title',
                'slug',
                'body',
                'published',
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'tags' => [
                    '*' => ['id', 'name', 'slug'],
                ],
                'categories' => [
                    '*' => ['id', 'name', 'slug'],
                ],
                'comments_count',
                'likes_count',
                'created_at',
                'updated_at',
            ],
        ],
        'current_page',
        'last_page',
        'total',
        'per_page',
    ]);
    expect($response->json('per_page'))->toBe(10);
    expect(count($response->json('data')))->toBe(10);
});

test('GET /posts?page=2 paginates correctly', function () {
    // Arrange
    Post::factory(25)->create(['published' => true]);

    // Act
    $response = $this->getJson('/api/posts?page=2');

    // Assert
    $response->assertStatus(200);
    expect($response->json('current_page'))->toBe(2);
    expect(count($response->json('data')))->toBe(10);
});

test('GET /posts filters by published=true', function () {
    // Arrange
    Post::factory(5)->create(['published' => true]);
    Post::factory(5)->create(['published' => false]);

    // Act
    $response = $this->getJson('/api/posts?published=true');

    // Assert
    $response->assertStatus(200);
    expect($response->json('total'))->toBe(5);
    foreach ($response->json('data') as $post) {
        expect($post['published'])->toBeTrue();
    }
});

test('GET /posts filters by published=false', function () {
    // Arrange
    Post::factory(5)->create(['published' => true]);
    Post::factory(5)->create(['published' => false]);

    // Act
    $response = $this->getJson('/api/posts?published=false');

    // Assert
    $response->assertStatus(200);
    expect($response->json('total'))->toBe(5);
    foreach ($response->json('data') as $post) {
        expect($post['published'])->toBeFalse();
    }
});

test('GET /posts filters by category', function () {
    // Arrange
    $category = Category::factory()->create();
    $post1 = Post::factory()->create(['published' => true]);
    $post2 = Post::factory()->create(['published' => true]);
    $post1->categories()->attach($category);

    // Act
    $response = $this->getJson("/api/posts?category={$category->id}");

    // Assert
    $response->assertStatus(200);
    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.id'))->toBe($post1->id);
});

test('GET /posts filters by tag', function () {
    // Arrange
    $tag = Tag::factory()->create();
    $post1 = Post::factory()->create(['published' => true]);
    $post2 = Post::factory()->create(['published' => true]);
    $post1->tags()->attach($tag);

    // Act
    $response = $this->getJson("/api/posts?tag={$tag->id}");

    // Assert
    $response->assertStatus(200);
    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.id'))->toBe($post1->id);
});

test('GET /posts searches by title', function () {
    // Arrange
    Post::factory()->create(['title' => 'Laravel Tutorial', 'published' => true]);
    Post::factory()->create(['title' => 'React Guide', 'published' => true]);

    // Act
    $response = $this->getJson('/api/posts?q=Laravel');

    // Assert
    $response->assertStatus(200);
    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.title'))->toContain('Laravel');
});

test('GET /posts orders by newest first', function () {
    // Arrange
    $post1 = Post::factory()->create(['published' => true, 'created_at' => now()->subDays(2)]);
    $post2 = Post::factory()->create(['published' => true, 'created_at' => now()->subDay()]);
    $post3 = Post::factory()->create(['published' => true, 'created_at' => now()]);

    // Act
    $response = $this->getJson('/api/posts');

    // Assert
    expect($response->json('data.0.id'))->toBe($post3->id);
    expect($response->json('data.1.id'))->toBe($post2->id);
    expect($response->json('data.2.id'))->toBe($post1->id);
});

// ============================================================================
// GET /api/posts/{id} — Show Single Post (public)
// ============================================================================

test('GET /posts/{id} returns single post with full structure', function () {
    // Arrange
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $category = Category::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    $post->tags()->attach($tag);
    $post->categories()->attach($category);
    Comment::factory()->create(['post_id' => $post->id]);
    Like::factory()->create(['post_id' => $post->id]);

    // Act
    $response = $this->getJson("/api/posts/{$post->id}");

    // Assert
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'id',
        'user_id',
        'title',
        'slug',
        'body',
        'published',
        'user' => ['id', 'name', 'email'],
        'tags' => ['*' => ['id', 'name', 'slug']],
        'categories' => ['*' => ['id', 'name', 'slug']],
        'comments' => [
            '*' => [
                'id',
                'post_id',
                'user_id',
                'body',
                'user' => ['id', 'name', 'email'],
                'created_at',
                'updated_at',
            ],
        ],
        'likes_count',
        'created_at',
        'updated_at',
    ]);
    expect($response->json('id'))->toBe($post->id);
    expect($response->json('user_id'))->toBe($user->id);
    expect($response->json('title'))->toBe($post->title);
    expect($response->json('likes_count'))->toBe(1);
});

test('GET /posts/{id} returns 404 for non-existent post', function () {
    // Act
    $response = $this->getJson('/api/posts/99999');

    // Assert
    $response->assertStatus(404);
    $response->assertJson(['message' => 'Post not found']);
});

// ============================================================================
// POST /api/posts — Create Post (authenticated)
// ============================================================================

test('POST /posts creates a post with title and body', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Test Post Title',
        'body' => 'This is the post body.',
        'published' => false,
    ]);

    // Assert
    $response->assertStatus(201);
    $response->assertJsonStructure([
        'id',
        'user_id',
        'title',
        'slug',
        'body',
        'published',
        'tags',
        'categories',
    ]);
    expect($response->json('user_id'))->toBe($user->id);
    expect($response->json('title'))->toBe('Test Post Title');
    expect($response->json('body'))->toBe('This is the post body.');
    expect($response->json('published'))->toBeFalse();
    $this->assertDatabaseHas('posts', [
        'user_id' => $user->id,
        'title' => 'Test Post Title',
    ]);
});

test('POST /posts assigns slug automatically', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'My Awesome Post',
        'body' => 'Content here.',
    ]);

    // Assert
    $response->assertStatus(201);
    expect($response->json('slug'))->toContain('my-awesome-post');
});

test('POST /posts sets published to false by default', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Test',
        'body' => 'Content',
    ]);

    // Assert
    $response->assertStatus(201);
    expect($response->json('published'))->toBeFalse();
});

test('POST /posts attaches tags by ID', function () {
    // Arrange
    $user = User::factory()->create();
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Tagged Post',
        'body' => 'Content',
        'tags' => [$tag1->id, $tag2->id],
    ]);

    // Assert
    $response->assertStatus(201);
    expect(count($response->json('tags')))->toBe(2);
    expect($response->json('tags.0.id'))->toBe($tag1->id);
    expect($response->json('tags.1.id'))->toBe($tag2->id);
    $this->assertDatabaseHas('post_tag', [
        'post_id' => $response->json('id'),
        'tag_id' => $tag1->id,
    ]);
});

test('POST /posts attaches categories by ID', function () {
    // Arrange
    $user = User::factory()->create();
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Categorized Post',
        'body' => 'Content',
        'categories' => [$cat1->id, $cat2->id],
    ]);

    // Assert
    $response->assertStatus(201);
    expect(count($response->json('categories')))->toBe(2);
    $this->assertDatabaseHas('category_post', [
        'post_id' => $response->json('id'),
        'category_id' => $cat1->id,
    ]);
});

test('POST /posts requires authentication (401)', function () {
    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Test',
        'body' => 'Content',
    ]);

    // Assert
    $response->assertStatus(401);
});

test('POST /posts validates title required', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'body' => 'Content without title',
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('title');
});

test('POST /posts validates body required', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Title without body',
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('body');
});

test('POST /posts validates title max 255 characters', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => str_repeat('a', 256),
        'body' => 'Content',
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('title');
});

test('POST /posts validates tags must exist', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Test',
        'body' => 'Content',
        'tags' => [99999],
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('tags.0');
});

test('POST /posts validates categories must exist', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Test',
        'body' => 'Content',
        'categories' => [99999],
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('categories.0');
});

test('POST /posts validates published must be boolean', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Test',
        'body' => 'Content',
        'published' => 'not-a-boolean',
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('published');
});

// ============================================================================
// PUT /api/posts/{id} — Update Post (authenticated, author only)
// ============================================================================

test('PUT /posts/{id} updates post fields', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'title' => 'Updated Title',
        'body' => 'Updated body content.',
        'published' => true,
    ]);

    // Assert
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'id',
        'user_id',
        'title',
        'slug',
        'body',
        'published',
        'tags',
        'categories',
    ]);
    expect($response->json('title'))->toBe('Updated Title');
    expect($response->json('body'))->toBe('Updated body content.');
    expect($response->json('published'))->toBeTrue();
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
    ]);
});

test('PUT /posts/{id} allows partial updates', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'Original Title',
        'body' => 'Original body',
        'published' => false,
    ]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'title' => 'New Title Only',
    ]);

    // Assert
    $response->assertStatus(200);
    expect($response->json('title'))->toBe('New Title Only');
    expect($response->json('body'))->toBe('Original body');
    expect($response->json('published'))->toBeFalse();
});

test('PUT /posts/{id} syncs tags', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    $oldTag = Tag::factory()->create();
    $newTag = Tag::factory()->create();
    $post->tags()->attach($oldTag);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'tags' => [$newTag->id],
    ]);

    // Assert
    $response->assertStatus(200);
    expect(count($response->json('tags')))->toBe(1);
    expect($response->json('tags.0.id'))->toBe($newTag->id);
    $this->assertDatabaseMissing('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $oldTag->id,
    ]);
});

test('PUT /posts/{id} syncs categories', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    $oldCat = Category::factory()->create();
    $newCat = Category::factory()->create();
    $post->categories()->attach($oldCat);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'categories' => [$newCat->id],
    ]);

    // Assert
    $response->assertStatus(200);
    expect(count($response->json('categories')))->toBe(1);
    $this->assertDatabaseMissing('category_post', [
        'post_id' => $post->id,
        'category_id' => $oldCat->id,
    ]);
});

test('PUT /posts/{id} clears tags when empty array passed', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    $tag = Tag::factory()->create();
    $post->tags()->attach($tag);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'tags' => [],
    ]);

    // Assert
    $response->assertStatus(200);
    expect($response->json('tags'))->toEqual([]);
    $this->assertDatabaseMissing('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $tag->id,
    ]);
});

test('PUT /posts/{id} requires authentication (401)', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'title' => 'Updated',
    ]);

    // Assert
    $response->assertStatus(401);
});

test('PUT /posts/{id} denies non-author (403)', function () {
    // Arrange
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($otherUser);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'title' => 'Hacked Title',
    ]);

    // Assert
    $response->assertStatus(403);
    $response->assertJson(['message' => 'Forbidden']);
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => $post->title,
    ]);
});

test('PUT /posts/{id} returns 404 for non-existent post', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson('/api/posts/99999', [
        'title' => 'Updated',
    ]);

    // Assert
    $response->assertStatus(404);
    $response->assertJson(['message' => 'Post not found']);
});

test('PUT /posts/{id} validates title max 255', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'title' => str_repeat('a', 256),
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('title');
});

test('PUT /posts/{id} validates tags must exist', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'tags' => [99999],
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('tags.0');
});

// ============================================================================
// DELETE /api/posts/{id} — Delete Post (authenticated, author only)
// ============================================================================

test('DELETE /posts/{id} deletes the post', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertStatus(200);
    $response->assertJson(['message' => 'deleted']);
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('DELETE /posts/{id} requires authentication (401)', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertStatus(401);
});

test('DELETE /posts/{id} denies non-author (403)', function () {
    // Arrange
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($otherUser);

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertStatus(403);
    $response->assertJson(['message' => 'Forbidden']);
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

test('DELETE /posts/{id} returns 404 for non-existent post', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->deleteJson('/api/posts/99999');

    // Assert
    $response->assertStatus(404);
    $response->assertJson(['message' => 'Post not found']);
});
