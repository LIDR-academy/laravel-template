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
|
| Written FIRST against the messy inline closures and run green, these lock
| the CURRENT observable contract of the five Posts endpoints before the
| refactor into the layered architecture. They must stay green at every step:
| moving to Controller/FormRequest/Service/Resource may NOT change the
| response shape, status codes or auth boundaries.
|--------------------------------------------------------------------------
*/

// --- GET /api/posts (public list) ---------------------------------------

it('lists posts with relations, counts and the flat pagination envelope', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'user')->create(['published' => true]);
    $post->tags()->attach(Tag::factory()->create());
    $post->categories()->attach(Category::factory()->create());
    Comment::factory()->count(2)->create(['post_id' => $post->id]);
    Like::factory()->create(['post_id' => $post->id]);

    // Act
    $response = $this->getJson('/api/posts');

    // Assert — full shape + flat paginator keys
    $response->assertOk()
        ->assertJsonStructure([
            'current_page',
            'per_page',
            'total',
            'last_page',
            'data' => [
                '*' => [
                    'id', 'user_id', 'title', 'slug', 'body', 'published',
                    'created_at', 'updated_at',
                    'comments_count', 'likes_count',
                    'user' => ['id', 'name', 'email'],
                    'tags' => ['*' => ['id', 'name', 'slug']],
                    'categories' => ['*' => ['id', 'name', 'slug']],
                ],
            ],
        ]);

    expect($response->json('per_page'))->toBe(10);
    expect($response->json('total'))->toBe(1);

    $row = $response->json('data.0');
    expect($row['id'])->toBe($post->id);
    expect($row['comments_count'])->toBe(2);
    expect($row['likes_count'])->toBe(1);
    expect($row['published'])->toBeBool()->toBeTrue();
    // The list view must NOT embed the comments collection (counts only).
    expect($row)->not->toHaveKey('comments');
});

it('orders listed posts newest-first by id', function () {
    // Arrange
    $older = Post::factory()->create();
    $newer = Post::factory()->create();

    // Act
    $response = $this->getJson('/api/posts');

    // Assert
    expect($response->json('data.0.id'))->toBe($newer->id);
    expect($response->json('data.1.id'))->toBe($older->id);
});

it('filters listed posts by published, category, tag and title query', function () {
    // Arrange
    $tag = Tag::factory()->create();
    $category = Category::factory()->create();

    $match = Post::factory()->create(['title' => 'Laravel Testing Guide', 'published' => true]);
    $match->tags()->attach($tag);
    $match->categories()->attach($category);

    $draft = Post::factory()->create(['title' => 'Unrelated Draft', 'published' => false]);

    // Act + Assert — each filter resolves to the matching post
    expect($this->getJson('/api/posts?published=1')->json('data'))->toHaveCount(1);
    expect($this->getJson('/api/posts?published=0')->json('data.0.id'))->toBe($draft->id);
    expect($this->getJson("/api/posts?category={$category->id}")->json('data.0.id'))->toBe($match->id);
    expect($this->getJson("/api/posts?tag={$tag->id}")->json('data.0.id'))->toBe($match->id);
    expect($this->getJson('/api/posts?q=Laravel')->json('data.0.id'))->toBe($match->id);
});

// --- GET /api/posts/{id} (public show) ----------------------------------

it('shows a single post with comments, authors and likes count, unwrapped', function () {
    // Arrange
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'user')->create(['published' => true]);
    $post->tags()->attach(Tag::factory()->create());
    $post->categories()->attach(Category::factory()->create());
    Comment::factory()->create(['post_id' => $post->id]);
    Like::factory()->count(3)->create(['post_id' => $post->id]);

    // Act
    $response = $this->getJson("/api/posts/{$post->id}");

    // Assert — full contract, no `data` envelope, embeds comments collection
    $response->assertOk()
        ->assertJsonStructure([
            'id', 'user_id', 'title', 'slug', 'body', 'published',
            'created_at', 'updated_at', 'likes_count',
            'user' => ['id', 'name', 'email'],
            'tags' => ['*' => ['id', 'name', 'slug']],
            'categories' => ['*' => ['id', 'name', 'slug']],
            'comments' => ['*' => ['id', 'body', 'user' => ['id', 'name', 'email']]],
        ]);

    expect($response->json('id'))->toBe($post->id);
    expect($response->json('likes_count'))->toBe(3);
    expect($response->json('published'))->toBeBool()->toBeTrue();
});

it('returns 404 with a message for a missing post on show', function () {
    // Arrange + Act
    $response = $this->getJson('/api/posts/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

// --- POST /api/posts (auth create) --------------------------------------

it('creates a post for the authenticated user with synced tags and categories', function () {
    // Arrange
    $user = User::factory()->create();
    $tags = Tag::factory()->count(2)->create();
    $category = Category::factory()->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'A Brand New Post',
        'body' => 'Body content for the post.',
        'published' => true,
        'tags' => $tags->pluck('id')->all(),
        'categories' => [$category->id],
    ]);

    // Assert — 201, unwrapped post with tags + categories embedded
    $response->assertCreated()
        ->assertJsonStructure([
            'id', 'user_id', 'title', 'slug', 'body', 'published',
            'created_at', 'updated_at',
            'tags' => ['*' => ['id', 'name', 'slug']],
            'categories' => ['*' => ['id', 'name', 'slug']],
        ]);

    expect($response->json('user_id'))->toBe($user->id);
    expect($response->json('title'))->toBe('A Brand New Post');
    expect($response->json('slug'))->toStartWith('a-brand-new-post-');
    expect($response->json('published'))->toBeTrue();
    expect($response->json('tags'))->toHaveCount(2);
    expect($response->json('categories'))->toHaveCount(1);

    $this->assertDatabaseHas('posts', [
        'title' => 'A Brand New Post',
        'user_id' => $user->id,
        'published' => true,
    ]);
    expect(Post::find($response->json('id'))->tags->pluck('id')->sort()->values()->all())
        ->toBe($tags->pluck('id')->sort()->values()->all());
});

it('defaults published to false when omitted on create', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Draft Post',
        'body' => 'Some body.',
    ]);

    // Assert
    $response->assertCreated();
    expect($response->json('published'))->toBeFalse();
    $this->assertDatabaseHas('posts', ['title' => 'Draft Post', 'published' => false]);
});

it('rejects post creation with validation errors (422)', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act — missing title, blank body, non-existent tag
    $response = $this->postJson('/api/posts', [
        'body' => '',
        'tags' => [999999],
    ]);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['title', 'body', 'tags.0']);
});

it('rejects post creation when unauthenticated (401)', function () {
    // Arrange + Act
    $response = $this->postJson('/api/posts', [
        'title' => 'Nope',
        'body' => 'Nope',
    ]);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseMissing('posts', ['title' => 'Nope']);
});

// --- PUT /api/posts/{id} (auth update, author only) ---------------------

it('updates a post owned by the authenticated user', function () {
    // Arrange
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $post = Post::factory()->for($user, 'user')->create(['title' => 'Old', 'published' => false]);
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", [
        'title' => 'Updated Title',
        'published' => true,
        'tags' => [$tag->id],
    ]);

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'id', 'user_id', 'title', 'slug', 'body', 'published',
            'tags' => ['*' => ['id', 'name', 'slug']],
            'categories',
        ]);

    expect($response->json('title'))->toBe('Updated Title');
    expect($response->json('published'))->toBeTrue();
    expect($response->json('tags'))->toHaveCount(1);
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
        'published' => true,
    ]);
});

it('forbids updating a post owned by another user (403)', function () {
    // Arrange
    $owner = User::factory()->create();
    $post = Post::factory()->for($owner, 'user')->create(['title' => 'Owned']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'Hijacked']);

    // Assert
    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Owned']);
});

it('returns 404 when updating a missing post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson('/api/posts/999999', ['title' => 'X']);

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

it('rejects update with validation errors (422)', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->for($user, 'user')->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => '']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['title']);
});

it('rejects update when unauthenticated (401)', function () {
    // Arrange
    $post = Post::factory()->create(['title' => 'Owned']);

    // Act
    $response = $this->putJson("/api/posts/{$post->id}", ['title' => 'Hijacked']);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Owned']);
});

// --- DELETE /api/posts/{id} (auth delete, author only) ------------------

it('deletes a post owned by the authenticated user', function () {
    // Arrange
    $user = User::factory()->create();
    $post = Post::factory()->for($user, 'user')->create();
    Sanctum::actingAs($user);

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertOk()->assertJson(['message' => 'deleted']);
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('forbids deleting a post owned by another user (403)', function () {
    // Arrange
    $owner = User::factory()->create();
    $post = Post::factory()->for($owner, 'user')->create();
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

it('returns 404 when deleting a missing post', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson('/api/posts/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Post not found']);
});

it('rejects delete when unauthenticated (401)', function () {
    // Arrange
    $post = Post::factory()->create();

    // Act
    $response = $this->deleteJson("/api/posts/{$post->id}");

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});
