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
|--------------------------------------------------------------------------
| These lock the CURRENT response contract of the inline toggle route in
| routes/api.php BEFORE the layered refactor (Controller / FormRequest /
| LikeService / Resource). They must stay green throughout the refactor:
| any drift in status, JSON shape, types, or DB side effects is a
| regression. Mirrors the patterns established for the Posts/Comments
| endpoints. No ownership policy — any authenticated user may like.
*/

// --- POST /api/posts/{id}/like (toggle, auth) ---------------------------

it('toggles a like ON for the authenticated user (happy path)', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert — full contract: exact key set, types, and DB side effect
    $response->assertOk()
        ->assertJsonStructure(['liked', 'likes_count']);

    expect($response->json('liked'))->toBeTrue();
    expect($response->json('likes_count'))->toBe(1);
    expect(array_keys($response->json()))->toBe(['liked', 'likes_count']);

    expect(Like::where('post_id', $post->id)->where('user_id', $user->id)->exists())->toBeTrue();
    expect(Like::where('post_id', $post->id)->count())->toBe(1);
});

it('toggles a like OFF when the user already liked the post', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Like::factory()->create(['post_id' => $post->id, 'user_id' => $user->id]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert — toggled off, like row removed, count back to zero
    $response->assertOk();
    expect($response->json('liked'))->toBeFalse();
    expect($response->json('likes_count'))->toBe(0);
    expect(Like::where('post_id', $post->id)->where('user_id', $user->id)->exists())->toBeFalse();
});

it('reports likes_count across all users, toggling only the callers like', function () {
    // Arrange — another user already likes the post
    $other = User::factory()->create();
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Like::factory()->create(['post_id' => $post->id, 'user_id' => $other->id]);
    Sanctum::actingAs($user);

    // Act — caller likes it too
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert — caller's like added, count reflects both users
    $response->assertOk();
    expect($response->json('liked'))->toBeTrue();
    expect($response->json('likes_count'))->toBe(2);
    expect(Like::where('post_id', $post->id)->count())->toBe(2);
});

it('returns 401 when unauthenticated', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->postJson("/api/posts/{$post->id}/like");

    // Assert — no like created
    $response->assertUnauthorized();
    expect(Like::count())->toBe(0);
});

it('returns 404 with a message when the post is missing', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts/999999/like');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
    expect(Like::count())->toBe(0);
});
