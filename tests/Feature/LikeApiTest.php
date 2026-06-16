<?php

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Characterization tests for the Likes endpoint.
|
| These lock the CURRENT observable contract of the single Likes route
| BEFORE refactoring it out of routes/api.php into the layered
| architecture (Controller → FormRequest → Service → Resource + Policy):
|
|   POST /api/posts/{id}/like   (auth toggle)
|
| The endpoint toggles the authenticated user's like on a post and returns
| an unwrapped { liked, likes_count } body with a 200 status. These tests
| must stay green at every step of the refactor — the response shape,
| status codes and auth boundaries may not change.
|--------------------------------------------------------------------------
*/

// --- POST /api/posts/{id}/like (auth toggle) ----------------------------

it('likes a post for the authenticated user on the first toggle', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert — 200, unwrapped { liked, likes_count } with correct types
    $response->assertOk()
        ->assertJsonStructure(['liked', 'likes_count']);

    expect($response->json('liked'))->toBeTrue();
    expect($response->json('likes_count'))->toBe(1);

    $this->assertDatabaseHas('likes', [
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);
});

it('unlikes a post for the authenticated user on the second toggle', function () {
    // Arrange — the user has already liked this post
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Like::factory()->create(['post_id' => $post->id, 'user_id' => $user->id]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert — 200, like removed, count back to zero
    $response->assertOk()
        ->assertJsonStructure(['liked', 'likes_count']);

    expect($response->json('liked'))->toBeFalse();
    expect($response->json('likes_count'))->toBe(0);

    $this->assertDatabaseMissing('likes', [
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);
});

it('counts only the likes belonging to the toggled post', function () {
    // Arrange — another user already likes this post; a like on another
    // post must never leak into this post's count.
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Like::factory()->create(['post_id' => $post->id]);
    Like::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert — existing other-user like + this user's new like = 2
    $response->assertOk();
    expect($response->json('liked'))->toBeTrue();
    expect($response->json('likes_count'))->toBe(2);
});

it('returns 404 when liking a missing post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/posts/999999/like');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

it('rejects liking when unauthenticated (401)', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseMissing('likes', ['post_id' => $post->id]);
});
