<?php

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Characterization tests for POST /api/posts/{id}/like
|
| Written FIRST against the messy inline closure and run green, these lock
| the CURRENT observable contract before the refactor into the layered
| architecture. They must stay green at every step.
|--------------------------------------------------------------------------
*/

// --- Happy path: toggle on --------------------------------------------------

it('toggles a like on and returns liked=true with the current likes_count', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert — full response shape
    $response->assertOk()
        ->assertJsonStructure(['liked', 'likes_count'])
        ->assertJson(['liked' => true, 'likes_count' => 1]);

    $this->assertDatabaseHas('likes', [
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);
});

// --- Happy path: toggle off --------------------------------------------------

it('toggles a like off on a second request and returns liked=false', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Like::factory()->create(['post_id' => $post->id, 'user_id' => $user->id]);
    Sanctum::actingAs($user);

    // Act — user already liked, second hit removes it
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert
    $response->assertOk()
        ->assertJson(['liked' => false, 'likes_count' => 0]);

    $this->assertDatabaseMissing('likes', [
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);
});

// --- likes_count reflects all users' likes ----------------------------------

it('returns the total likes_count across all users, not just the requesting user', function () {
    // Arrange — two other users have already liked the post
    $post = Post::factory()->create();
    Like::factory()->count(2)->create(['post_id' => $post->id]);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert
    $response->assertOk()->assertJson(['liked' => true, 'likes_count' => 3]);
});

// --- Auth failure -----------------------------------------------------------

it('returns 401 when liking without authentication', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseEmpty('likes');
});

// --- 404 on missing post ----------------------------------------------------

it('returns 404 with a message when the post does not exist', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/posts/999999/like');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
    $this->assertDatabaseEmpty('likes');
});
