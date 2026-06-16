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
|--------------------------------------------------------------------------
| These lock the CURRENT response contract of the inline routes in
| routes/api.php BEFORE the layered refactor (Controller / FormRequest /
| Service / Resource / CommentPolicy). They must stay green throughout the
| refactor: any drift in status, JSON shape, types, or DB side effects is a
| regression. Mirrors the patterns established for the Posts endpoints.
*/

// --- GET /api/posts/{id}/comments (index, public) -----------------------

it('lists a posts comments with the full contract (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    $older = Comment::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);
    $newer = Comment::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);

    // Act
    $response = $this->getJson("/api/posts/{$post->id}/comments");

    // Assert — bare array (no data wrapper), full per-item shape with user loaded
    $response->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id', 'post_id', 'user_id', 'body', 'created_at', 'updated_at',
                'user' => ['id', 'name', 'email'],
            ],
        ]);

    expect($response->json())->toHaveCount(2);
    // ordered by id descending — newest comment first
    expect($response->json('0.id'))->toBe($newer->id);
    expect($response->json('1.id'))->toBe($older->id);
    expect($response->json('0.post_id'))->toBe($post->id);
    expect($response->json('0.user.id'))->toBe($author->id);
    expect($response->json('0.created_at'))->toBeString();
});

it('returns an empty array for a post with no comments', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->getJson("/api/posts/{$post->id}/comments");

    // Assert
    $response->assertOk();
    expect($response->json())->toBe([]);
});

it('returns 404 with a message listing comments for a missing post', function () {
    // Act
    $response = $this->getJson('/api/posts/999999/comments');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

// --- POST /api/posts/{id}/comments (store, auth) ------------------------

it('creates a comment for the authenticated user (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->create();
    Sanctum::actingAs($author);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/comments", [
        'body' => 'Great write-up, thanks!',
    ]);

    // Assert — 201 + bare object + user relation
    $response->assertCreated()
        ->assertJsonStructure([
            'id', 'post_id', 'user_id', 'body', 'created_at', 'updated_at',
            'user' => ['id', 'name', 'email'],
        ]);

    expect($response->json('body'))->toBe('Great write-up, thanks!');
    expect($response->json('post_id'))->toBe($post->id);
    expect($response->json('user_id'))->toBe($author->id);
    expect($response->json('user.id'))->toBe($author->id);

    $this->assertDatabaseHas('comments', [
        'id' => $response->json('id'),
        'post_id' => $post->id,
        'user_id' => $author->id,
        'body' => 'Great write-up, thanks!',
    ]);
});

it('rejects an invalid comment payload with 422', function () {
    // Arrange
    $post = Post::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    // Act — body is required, string, max:2000
    $response = $this->postJson("/api/posts/{$post->id}/comments", [
        'body' => str_repeat('x', 2001),
    ]);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['body']);
});

it('requires the body field when creating a comment (422)', function () {
    // Arrange
    $post = Post::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/comments", []);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['body']);
});

it('returns 404 when commenting on a missing post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/posts/999999/comments', ['body' => 'hello']);

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

it('blocks unauthenticated comment creation with 401', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/comments", ['body' => 'hi']);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseCount('comments', 0);
});

// --- DELETE /api/comments/{id} (destroy, auth, author-only) -------------

it('deletes a comment owned by the authenticated author (happy path)', function () {
    // Arrange
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($author);

    // Act
    $response = $this->deleteJson("/api/comments/{$comment->id}");

    // Assert
    $response->assertOk()->assertJson(['message' => 'deleted']);
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

it('returns 404 when deleting a missing comment', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson('/api/comments/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Comment not found']);
});

it('blocks deleting a comment by a non-author with 403', function () {
    // Arrange
    $author = User::factory()->create();
    $intruder = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);
    Sanctum::actingAs($intruder);

    // Act
    $response = $this->deleteJson("/api/comments/{$comment->id}");

    // Assert
    $response->assertForbidden()->assertJson(['message' => 'Forbidden']);
    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

it('blocks unauthenticated comment deletion with 401', function () {
    // Arrange
    $comment = Comment::factory()->create();

    // Act
    $response = $this->deleteJson("/api/comments/{$comment->id}");

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});
