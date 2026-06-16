<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Characterization tests for the Comments endpoints.
|
| These lock the CURRENT observable contract of the three Comments routes
| BEFORE refactoring them out of routes/api.php into the layered
| architecture (Controller → FormRequest → Service → Resource + Policy):
|
|   GET    /api/posts/{id}/comments   (public list, newest-first)
|   POST   /api/posts/{id}/comments   (auth create)
|   DELETE /api/comments/{id}         (auth delete, author only)
|
| They must stay green at every step of the refactor — the response shape,
| status codes and auth boundaries may not change.
|--------------------------------------------------------------------------
*/

// --- GET /api/posts/{id}/comments (public list) -------------------------

it('lists a post comments with authors, newest-first, as a bare array', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create();
    $older = Comment::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);
    $newer = Comment::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);
    // A comment on another post must never leak into this post's list.
    Comment::factory()->create();

    // Act
    $response = $this->getJson("/api/posts/{$post->id}/comments");

    // Assert — unwrapped (no `data` envelope), full per-comment shape
    $response->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id', 'post_id', 'user_id', 'body', 'created_at', 'updated_at',
                'user' => ['id', 'name', 'email'],
            ],
        ]);

    expect(array_is_list($response->json()))->toBeTrue();
    expect($response->json())->toHaveCount(2);
    // newest-first by id
    expect($response->json('0.id'))->toBe($newer->id);
    expect($response->json('1.id'))->toBe($older->id);
    expect($response->json('0.post_id'))->toBe($post->id);
    expect($response->json('0.user.id'))->toBe($author->id);
});

it('returns 404 when listing comments for a missing post', function () {
    // Arrange + Act
    $response = $this->getJson('/api/posts/999999/comments');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

// --- POST /api/posts/{id}/comments (auth create) ------------------------

it('creates a comment for the authenticated user on a post', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/comments", [
        'body' => 'Nice post!',
    ]);

    // Assert — 201, unwrapped comment with its author embedded
    $response->assertCreated()
        ->assertJsonStructure([
            'id', 'post_id', 'user_id', 'body', 'created_at', 'updated_at',
            'user' => ['id', 'name', 'email'],
        ]);

    expect($response->json('post_id'))->toBe($post->id);
    expect($response->json('user_id'))->toBe($user->id);
    expect($response->json('body'))->toBe('Nice post!');
    expect($response->json('user.id'))->toBe($user->id);

    $this->assertDatabaseHas('comments', [
        'post_id' => $post->id,
        'user_id' => $user->id,
        'body' => 'Nice post!',
    ]);
});

it('returns 404 when commenting on a missing post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/posts/999999/comments', ['body' => 'Hello']);

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

it('rejects comment creation with validation errors (422)', function () {
    // Arrange
    $post = Post::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    // Act — missing body
    $response = $this->postJson("/api/posts/{$post->id}/comments", []);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['body']);
});

it('rejects a comment body longer than 2000 characters (422)', function () {
    // Arrange
    $post = Post::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/comments", [
        'body' => str_repeat('a', 2001),
    ]);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['body']);
});

it('rejects comment creation when unauthenticated (401)', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/comments", ['body' => 'Nope']);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseMissing('comments', ['body' => 'Nope']);
});

// --- DELETE /api/comments/{id} (auth delete, author only) ---------------

it('deletes a comment owned by the authenticated user', function () {
    // Arrange
    $user = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->deleteJson("/api/comments/{$comment->id}");

    // Assert
    $response->assertOk()->assertJson(['message' => 'deleted']);
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

it('forbids deleting a comment owned by another user (403)', function () {
    // Arrange
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $owner->id]);
    Sanctum::actingAs($intruder);

    // Act
    $response = $this->deleteJson("/api/comments/{$comment->id}");

    // Assert
    $response->assertForbidden()->assertJson(['message' => 'Forbidden']);
    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

it('returns 404 when deleting a missing comment', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson('/api/comments/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Comment not found']);
});

it('rejects comment deletion when unauthenticated (401)', function () {
    // Arrange
    $comment = Comment::factory()->create();

    // Act
    $response = $this->deleteJson("/api/comments/{$comment->id}");

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});
