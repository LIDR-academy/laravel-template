<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// GET /api/posts  (index)
// ---------------------------------------------------------------------------

it('lists posts with pagination and counts', function () {
    // Arrange
    $tag = Tag::factory()->create(['name' => 'Laravel']);
    $category = Category::factory()->create(['name' => 'Backend']);
    $post = Post::factory()->create(['published' => true]);
    $post->tags()->attach($tag);
    $post->categories()->attach($category);

    // Act
    $response = $this->getJson('/api/posts');

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'title', 'slug', 'body', 'published',
                    'comments_count', 'likes_count',
                    'author' => ['id', 'name'],
                    'tags' => [['id', 'name']],
                    'categories' => [['id', 'name']],
                ],
            ],
            'meta' => ['current_page', 'per_page', 'total'],
            'links',
        ]);
});

it('filters posts by published flag', function () {
    // Arrange
    Post::factory()->create(['published' => true]);
    Post::factory()->create(['published' => false]);

    // Act
    $response = $this->getJson('/api/posts?published=1');

    // Assert
    $response->assertOk();
    collect($response->json('data'))->each(
        fn ($p) => expect($p['published'])->toBeTrue()
    );
});

it('filters posts by category', function () {
    // Arrange
    $category = Category::factory()->create();
    $match = Post::factory()->create();
    Post::factory()->create();
    $match->categories()->attach($category);

    // Act
    $response = $this->getJson("/api/posts?category={$category->id}");

    // Assert
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($match->id);
});

it('filters posts by tag', function () {
    // Arrange
    $tag = Tag::factory()->create();
    $match = Post::factory()->create();
    Post::factory()->create();
    $match->tags()->attach($tag);

    // Act
    $response = $this->getJson("/api/posts?tag={$tag->id}");

    // Assert
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($match->id);
});

it('searches posts by title keyword', function () {
    // Arrange
    Post::factory()->create(['title' => 'Laravel is great']);
    Post::factory()->create(['title' => 'Unrelated post']);

    // Act
    $response = $this->getJson('/api/posts?q=Laravel');

    // Assert
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.title'))->toBe('Laravel is great');
});

// ---------------------------------------------------------------------------
// GET /api/posts/{id}  (show)
// ---------------------------------------------------------------------------

it('shows a single post with comments and likes count', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->getJson("/api/posts/{$post->id}");

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'id', 'title', 'slug', 'body', 'published',
            'likes_count',
            'author' => ['id', 'name'],
            'tags' => [],
            'categories' => [],
            'comments' => [],
        ])
        ->assertJsonFragment(['id' => $post->id]);
});

it('returns 404 when post is not found on show', function () {
    // Arrange — no post created

    // Act
    $response = $this->getJson('/api/posts/999');

    // Assert
    $response->assertNotFound();
});

// ---------------------------------------------------------------------------
// POST /api/posts  (store)
// ---------------------------------------------------------------------------

it('creates a post and returns 201 with full shape', function () {
    // Arrange
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $category = Category::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'My New Post',
        'body' => 'Post body here.',
        'published' => true,
        'tags' => [$tag->id],
        'categories' => [$category->id],
    ]);

    // Assert
    $response->assertCreated()
        ->assertJsonStructure([
            'id', 'title', 'slug', 'body', 'published',
            'author' => ['id', 'name'],
            'tags' => [['id', 'name']],
            'categories' => [['id', 'name']],
        ])
        ->assertJsonFragment([
            'title' => 'My New Post',
            'published' => true,
        ]);

    expect(Post::where('title', 'My New Post')->exists())->toBeTrue();
});

it('returns 422 when required fields are missing on store', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/posts', []);

    // Assert
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'body']);
});

it('returns 401 when unauthenticated on store', function () {
    // Arrange — no auth

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'No auth post',
        'body' => 'body',
    ]);

    // Assert
    $response->assertUnauthorized();
});

// ---------------------------------------------------------------------------
// PUT /api/posts/{id}  (update)
// ---------------------------------------------------------------------------

it('updates a post and returns full shape', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'title' => 'Updated Title',
    ]);

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'id', 'title', 'slug', 'body', 'published',
            'author' => ['id', 'name'],
            'tags' => [],
            'categories' => [],
        ])
        ->assertJsonFragment(['title' => 'Updated Title']);

    expect(Post::find($post->id)->title)->toBe('Updated Title');
});

it('returns 403 when a non-owner tries to update a post', function () {
    // Arrange
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);
    Sanctum::actingAs($other);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'Hack']);

    // Assert
    $response->assertForbidden();
});

it('returns 404 when updating a non-existent post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson('/api/posts/999', ['title' => 'x']);

    // Assert
    $response->assertNotFound();
});

it('returns 401 when unauthenticated on update', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'x']);

    // Assert
    $response->assertUnauthorized();
});

// ---------------------------------------------------------------------------
// DELETE /api/posts/{id}  (destroy)
// ---------------------------------------------------------------------------

it('deletes a post and returns success message', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertOk()
        ->assertJsonFragment(['message' => 'deleted']);

    expect(Post::find($post->id))->toBeNull();
});

it('returns 403 when a non-owner tries to delete a post', function () {
    // Arrange
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);
    Sanctum::actingAs($other);

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertForbidden();
});

it('returns 404 when deleting a non-existent post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson('/api/posts/999');

    // Assert
    $response->assertNotFound();
});

it('returns 401 when unauthenticated on delete', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertUnauthorized();
});
