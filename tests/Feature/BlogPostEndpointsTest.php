<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('POST /api/login returns a token', function () {
    $user = User::factory()->create([
        'email' => 'demo@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'demo@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    expect($response->json('token'))->not->toBeEmpty();
});

test('POST /api/posts creates a post', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/posts', [
        'title' => 'A brand new post',
        'body' => 'Some body content for the post.',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('posts', ['title' => 'A brand new post']);
});

test('POST /api/posts/{id}/comments creates a comment', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/posts/{$post->id}/comments", [
        'body' => 'Nice post!',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('comments', ['post_id' => $post->id]);
});

test('POST /api/posts/{id}/like toggles a like on', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/posts/{$post->id}/like");

    $response->assertStatus(200);
    $this->assertDatabaseHas('likes', [
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);
});
