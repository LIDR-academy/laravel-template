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

/*
|--------------------------------------------------------------------------
| Characterization tests for the Posts endpoints.
|--------------------------------------------------------------------------
| These lock the CURRENT response contract of the inline routes in
| routes/api.php BEFORE the layered refactor (Controller / FormRequest /
| Service / Resource / Policy). They must stay green throughout the refactor:
| any drift in status, JSON shape, types, or DB side effects is a regression.
*/

// --- GET /api/posts (index, public) -------------------------------------

it('lists posts with the full paginated contract (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'published' => true]);
    $tag = Tag::factory()->create();
    $category = Category::factory()->create();
    $post->tags()->sync([$tag->id]);
    $post->categories()->sync([$category->id]);
    Comment::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);
    Like::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);

    // Act
    $response = $this->getJson('/api/posts');

    // Assert — paginator envelope + per-item shape
    $response->assertOk()
        ->assertJsonStructure([
            'current_page',
            'data' => [
                [
                    'id', 'user_id', 'title', 'slug', 'body', 'published',
                    'created_at', 'updated_at', 'comments_count', 'likes_count',
                    'user' => ['id', 'name', 'email'],
                    'tags' => [['id', 'name', 'slug', 'pivot' => ['post_id', 'tag_id']]],
                    'categories' => [['id', 'name', 'slug', 'pivot' => ['post_id', 'category_id']]],
                ],
            ],
            'first_page_url', 'from', 'last_page', 'last_page_url', 'links',
            'next_page_url', 'path', 'per_page', 'prev_page_url', 'to', 'total',
        ]);

    expect($response->json('per_page'))->toBe(10);
    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.id'))->toBe($post->id);
    expect($response->json('data.0.published'))->toBeBool()->toBeTrue();
    expect($response->json('data.0.comments_count'))->toBe(1);
    expect($response->json('data.0.likes_count'))->toBe(1);
});

it('orders posts by id descending', function () {
    // Arrange
    $first = Post::factory()->create();
    $second = Post::factory()->create();

    // Act
    $response = $this->getJson('/api/posts');

    // Assert
    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($second->id);
    expect($response->json('data.1.id'))->toBe($first->id);
});

it('filters posts by published flag', function () {
    // Arrange
    $published = Post::factory()->create(['published' => true]);
    $draft = Post::factory()->create(['published' => false]);

    // Act
    $response = $this->getJson('/api/posts?published=1');

    // Assert
    $response->assertOk();
    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.id'))->toBe($published->id);
});

it('filters posts by category, tag and title query', function () {
    // Arrange
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();
    $match = Post::factory()->create(['title' => 'Laravel Refactoring Guide']);
    $match->categories()->sync([$category->id]);
    $match->tags()->sync([$tag->id]);
    Post::factory()->create(['title' => 'Unrelated topic']);

    // Act + Assert — category
    expect($this->getJson("/api/posts?category={$category->id}")->json('data'))
        ->toHaveCount(1)
        ->and($this->getJson("/api/posts?category={$category->id}")->json('data.0.id'))->toBe($match->id);

    // Act + Assert — tag
    expect($this->getJson("/api/posts?tag={$tag->id}")->json('data'))->toHaveCount(1);

    // Act + Assert — title query
    expect($this->getJson('/api/posts?q=Refactoring')->json('data'))->toHaveCount(1);
});

// --- GET /api/posts/{id} (show, public) ---------------------------------

it('shows a single post with relations, comments and likes_count (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'published' => true]);
    $tag = Tag::factory()->create();
    $category = Category::factory()->create();
    $post->tags()->sync([$tag->id]);
    $post->categories()->sync([$category->id]);
    Comment::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);
    Like::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);

    // Act
    $response = $this->getJson("/api/posts/{$post->id}");

    // Assert — bare object (no data wrapper), full shape
    $response->assertOk()
        ->assertJsonStructure([
            'id', 'user_id', 'title', 'slug', 'body', 'published',
            'created_at', 'updated_at', 'likes_count',
            'user' => ['id', 'name', 'email'],
            'tags' => [['id', 'name', 'slug', 'pivot' => ['post_id', 'tag_id']]],
            'categories' => [['id', 'name', 'slug', 'pivot' => ['post_id', 'category_id']]],
            'comments' => [['id', 'post_id', 'user_id', 'body', 'created_at', 'updated_at', 'user' => ['id', 'name']]],
        ]);

    expect($response->json('id'))->toBe($post->id);
    expect($response->json('published'))->toBeBool()->toBeTrue();
    expect($response->json('likes_count'))->toBe(1);
    // show does NOT expose comments_count (only likes_count)
    expect($response->json())->not->toHaveKey('comments_count');
});

it('returns 404 with a message for a missing post on show', function () {
    // Act
    $response = $this->getJson('/api/posts/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

// --- POST /api/posts (store, auth) --------------------------------------

it('creates a post for the authenticated user (happy path)', function () {
    // Arrange
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $category = Category::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'My First Post',
        'body' => 'Body content here.',
        'published' => true,
        'tags' => [$tag->id],
        'categories' => [$category->id],
    ]);

    // Assert — 201 + bare object + relations, no counts
    $response->assertCreated()
        ->assertJsonStructure([
            'id', 'user_id', 'title', 'slug', 'body', 'published',
            'created_at', 'updated_at',
            'tags' => [['id', 'name', 'slug']],
            'categories' => [['id', 'name', 'slug']],
        ]);

    expect($response->json('title'))->toBe('My First Post');
    expect($response->json('user_id'))->toBe($user->id);
    expect($response->json('published'))->toBeBool()->toBeTrue();
    expect($response->json('slug'))->toStartWith('my-first-post-');
    expect($response->json('tags'))->toHaveCount(1);
    expect($response->json('categories'))->toHaveCount(1);

    $this->assertDatabaseHas('posts', [
        'id' => $response->json('id'),
        'title' => 'My First Post',
        'user_id' => $user->id,
        'published' => true,
    ]);
    $this->assertDatabaseHas('post_tag', ['post_id' => $response->json('id'), 'tag_id' => $tag->id]);
    $this->assertDatabaseHas('category_post', ['post_id' => $response->json('id'), 'category_id' => $category->id]);
});

it('defaults published to false when omitted on store', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', ['title' => 'Draft', 'body' => 'x']);

    // Assert
    $response->assertCreated();
    expect($response->json('published'))->toBeBool()->toBeFalse();
});

it('rejects an invalid store payload with 422 (validation matrix)', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'tags' => [999999],          // non-existent tag id
        'categories' => 'not-array', // wrong type
    ]);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'body', 'tags.0', 'categories']);
});

it('blocks unauthenticated post creation with 401', function () {
    // Act
    $response = $this->postJson('/api/posts', ['title' => 'X', 'body' => 'Y']);

    // Assert
    $response->assertUnauthorized();
});

// --- PUT /api/posts/{id} (update, auth, author-only) --------------------

it('updates a post owned by the authenticated author (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'published' => false]);
    $tag = Tag::factory()->create();
    Sanctum::actingAs($author);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'title' => 'Updated Title',
        'published' => true,
        'tags' => [$tag->id],
    ]);

    // Assert — 200 + bare object + relations
    $response->assertOk()
        ->assertJsonStructure([
            'id', 'user_id', 'title', 'slug', 'body', 'published',
            'created_at', 'updated_at',
            'tags' => [['id', 'name', 'slug']],
            'categories',
        ]);

    expect($response->json('title'))->toBe('Updated Title');
    expect($response->json('published'))->toBeBool()->toBeTrue();
    expect($response->json('tags'))->toHaveCount(1);

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated Title', 'published' => true]);
    $this->assertDatabaseHas('post_tag', ['post_id' => $post->id, 'tag_id' => $tag->id]);
});

it('rejects an invalid update payload with 422', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($author);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => '']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['title']);
});

it('returns 404 when updating a missing post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson('/api/posts/999999', ['title' => 'X']);

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

it('blocks updating a post by a non-author with 403', function () {
    // Arrange
    $author = User::factory()->create();
    $intruder = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'Original']);
    Sanctum::actingAs($intruder);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'Hijacked']);

    // Assert
    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Original']);
});

it('blocks unauthenticated update with 401', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'X']);

    // Assert
    $response->assertUnauthorized();
});

// --- DELETE /api/posts/{id} (delete, auth, author-only) -----------------

it('deletes a post owned by the authenticated author (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($author);

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertOk()->assertJson(['message' => 'deleted']);
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('returns 404 when deleting a missing post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson('/api/posts/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

it('blocks deleting a post by a non-author with 403', function () {
    // Arrange
    $author = User::factory()->create();
    $intruder = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($intruder);

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

it('blocks unauthenticated delete with 401', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertUnauthorized();
});
