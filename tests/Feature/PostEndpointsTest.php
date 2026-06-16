<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Characterization tests for the Posts endpoints
|--------------------------------------------------------------------------
| These lock the CURRENT, observable behavior of every Posts endpoint across
| the full matrix (happy / 422 / 401 / 404 / 403) BEFORE the refactor into the
| target architecture (Controller -> FormRequest -> Service -> Resource ->
| Policy). Assertions target the stable contract — status codes, payload
| values, and DB side effects — so they stay green through the extraction.
*/

// --- index: GET /api/posts (public) -------------------------------------

it('lists posts (happy path, public, paginated)', function () {
    // Arrange
    Post::factory()->count(3)->create();

    // Act
    $response = $this->getJson('/api/posts');

    // Assert
    $response->assertOk();
    $response->assertJsonStructure(['data']);
    expect($response->json('data'))->toHaveCount(3);
});

it('filters posts by published flag', function () {
    // Arrange
    Post::factory()->create(['title' => 'Visible', 'published' => true]);
    Post::factory()->create(['title' => 'Hidden', 'published' => false]);

    // Act
    $response = $this->getJson('/api/posts?published=1');

    // Assert
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonFragment(['title' => 'Visible']);
});

it('filters posts by tag, category and title query', function () {
    // Arrange
    $tag = Tag::factory()->create();
    $category = Category::factory()->create();
    $tagged = Post::factory()->create(['title' => 'Tagged unique alpha']);
    $tagged->tags()->sync([$tag->id]);
    $categorized = Post::factory()->create(['title' => 'Categorized unique beta']);
    $categorized->categories()->sync([$category->id]);
    Post::factory()->create(['title' => 'Unrelated gamma']);

    // Act
    $byTag = $this->getJson("/api/posts?tag={$tag->id}");
    $byCategory = $this->getJson("/api/posts?category={$category->id}");
    $byQuery = $this->getJson('/api/posts?q=alpha');

    // Assert
    expect($byTag->json('data'))->toHaveCount(1);
    $byTag->assertJsonFragment(['title' => 'Tagged unique alpha']);
    expect($byCategory->json('data'))->toHaveCount(1);
    $byCategory->assertJsonFragment(['title' => 'Categorized unique beta']);
    expect($byQuery->json('data'))->toHaveCount(1);
    $byQuery->assertJsonFragment(['title' => 'Tagged unique alpha']);
});

// --- show: GET /api/posts/{id} (public) ---------------------------------

it('shows a single post (happy path, public)', function () {
    // Arrange
    $post = Post::factory()->create(['title' => 'A specific post']);

    // Act
    $response = $this->getJson("/api/posts/{$post->id}");

    // Assert
    $response->assertOk();
    $response->assertJsonFragment(['title' => 'A specific post']);
});

it('returns 404 when showing a non-existent post', function () {
    // Arrange
    $missingId = 999999;

    // Act
    $response = $this->getJson("/api/posts/{$missingId}");

    // Assert
    $response->assertNotFound();
    $response->assertJsonFragment(['message' => 'Post not found']);
});

// --- store: POST /api/posts (auth) --------------------------------------

it('creates a post (happy path) with slug and published default false', function () {
    // Arrange
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $category = Category::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'My brand new post',
        'body' => 'The body of the post.',
        'tags' => [$tag->id],
        'categories' => [$category->id],
    ]);

    // Assert
    $response->assertCreated();
    $response->assertJsonFragment(['title' => 'My brand new post']);
    $post = Post::where('title', 'My brand new post')->first();
    expect($post)->not->toBeNull();
    expect($post->user_id)->toBe($user->id);
    expect($post->published)->toBeFalse();
    expect($post->slug)->not->toBeEmpty();
    expect($post->tags)->toHaveCount(1);
    expect($post->categories)->toHaveCount(1);
});

it('returns 422 when creating a post with missing required fields', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', []);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title', 'body']);
});

it('returns 401 when creating a post unauthenticated', function () {
    // Arrange
    $payload = ['title' => 'Nope', 'body' => 'No auth'];

    // Act
    $response = $this->postJson('/api/posts', $payload);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseMissing('posts', ['title' => 'Nope']);
});

// --- update: PUT /api/posts/{id} (auth, author-only) --------------------

it('updates a post as its author (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'Old title']);
    Sanctum::actingAs($author);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'New title']);

    // Assert
    $response->assertOk();
    $response->assertJsonFragment(['title' => 'New title']);
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'New title']);
});

it('returns 422 when updating a post with invalid data', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($author);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => '']);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title']);
});

it('returns 401 when updating a post unauthenticated', function () {
    // Arrange
    $post = Post::factory()->create(['title' => 'Untouched']);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'Hacked']);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Untouched']);
});

it('returns 404 when updating a non-existent post', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson('/api/posts/999999', ['title' => 'Ghost']);

    // Assert
    $response->assertNotFound();
    $response->assertJsonFragment(['message' => 'Post not found']);
});

it('returns 403 when updating a post owned by another user', function () {
    // Arrange
    $author = User::factory()->create();
    $intruder = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'Author owned']);
    Sanctum::actingAs($intruder);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'Stolen']);

    // Assert
    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Author owned']);
});

// --- delete: DELETE /api/posts/{id} (auth, author-only) -----------------

it('deletes a post as its author (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($author);

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertOk();
    $response->assertJsonFragment(['message' => 'deleted']);
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('returns 401 when deleting a post unauthenticated', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

it('returns 404 when deleting a non-existent post', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->deleteJson('/api/posts/999999');

    // Assert
    $response->assertNotFound();
    $response->assertJsonFragment(['message' => 'Post not found']);
});

it('returns 403 when deleting a post owned by another user', function () {
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
