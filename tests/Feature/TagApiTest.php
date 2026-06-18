<?php

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Feature tests for the Tags CRUD endpoints.
|
| Reads (index/show) are public; writes (store/update/destroy) require a
| Sanctum token. Tags have no owner, so authorization stops at
| authentication — there is no ownership/role boundary and no Policy.
| Responses are unwrapped (no top-level "data" envelope), matching the
| original raw-model contract of GET /api/tags.
|--------------------------------------------------------------------------
*/

// --- GET /api/tags (public list) ----------------------------------------

it('lists tags ordered by name, unwrapped', function () {
    // Arrange
    Tag::factory()->create(['name' => 'Zebra', 'slug' => 'zebra']);
    Tag::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);

    // Act
    $response = $this->getJson('/api/tags');

    // Assert — full contract, no pagination envelope, ordered A→Z
    $response->assertOk()
        ->assertJsonStructure([
            '*' => ['id', 'name', 'slug', 'created_at', 'updated_at'],
        ]);

    expect($response->json())->toHaveCount(2);
    expect($response->json('0.name'))->toBe('Alpha');
    expect($response->json('1.name'))->toBe('Zebra');
});

// --- GET /api/tags/{id} (public show) -----------------------------------

it('shows a single tag, unwrapped', function () {
    // Arrange
    $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

    // Act
    $response = $this->getJson("/api/tags/{$tag->id}");

    // Assert
    $response->assertOk()
        ->assertJsonStructure(['id', 'name', 'slug', 'created_at', 'updated_at']);

    expect($response->json('id'))->toBe($tag->id);
    expect($response->json('name'))->toBe('Laravel');
    expect($response->json('slug'))->toBe('laravel');
});

it('returns 404 with a message for a missing tag', function () {
    // Arrange + Act
    $response = $this->getJson('/api/tags/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Tag not found']);
});

// --- POST /api/tags (auth create) ---------------------------------------

it('creates a tag for the authenticated user with an auto-generated slug', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/tags', ['name' => 'Domain Driven Design']);

    // Assert
    $response->assertCreated()
        ->assertJsonStructure(['id', 'name', 'slug', 'created_at', 'updated_at']);

    expect($response->json('name'))->toBe('Domain Driven Design');
    expect($response->json('slug'))->toBe('domain-driven-design');
    $this->assertDatabaseHas('tags', [
        'name' => 'Domain Driven Design',
        'slug' => 'domain-driven-design',
    ]);
});

it('rejects tag creation with validation errors (422)', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/tags', ['name' => '']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('rejects creating a tag with a duplicate name (422)', function () {
    // Arrange
    Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/tags', ['name' => 'PHP']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    expect(Tag::where('name', 'PHP')->count())->toBe(1);
});

it('rejects tag creation when unauthenticated (401)', function () {
    // Arrange + Act
    $response = $this->postJson('/api/tags', ['name' => 'Nope']);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseMissing('tags', ['name' => 'Nope']);
});

// --- PUT /api/tags/{id} (auth update) -----------------------------------

it('updates a tag and regenerates its slug', function () {
    // Arrange
    $tag = Tag::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/tags/{$tag->id}", ['name' => 'New Name']);

    // Assert
    $response->assertOk()
        ->assertJsonStructure(['id', 'name', 'slug', 'created_at', 'updated_at']);

    expect($response->json('name'))->toBe('New Name');
    expect($response->json('slug'))->toBe('new-name');
    $this->assertDatabaseHas('tags', [
        'id' => $tag->id,
        'name' => 'New Name',
        'slug' => 'new-name',
    ]);
});

it('allows updating a tag while keeping its own name', function () {
    // Arrange
    $tag = Tag::factory()->create(['name' => 'Keep', 'slug' => 'keep']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/tags/{$tag->id}", ['name' => 'Keep']);

    // Assert — the unique rule must ignore the tag being updated
    $response->assertOk();
    expect($response->json('name'))->toBe('Keep');
});

it('rejects updating a tag to a name already taken (422)', function () {
    // Arrange
    Tag::factory()->create(['name' => 'Taken', 'slug' => 'taken']);
    $tag = Tag::factory()->create(['name' => 'Mine', 'slug' => 'mine']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/tags/{$tag->id}", ['name' => 'Taken']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('returns 404 when updating a missing tag', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson('/api/tags/999999', ['name' => 'X']);

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Tag not found']);
});

it('rejects update with validation errors (422)', function () {
    // Arrange
    $tag = Tag::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/tags/{$tag->id}", ['name' => '']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('rejects update when unauthenticated (401)', function () {
    // Arrange
    $tag = Tag::factory()->create(['name' => 'Owned', 'slug' => 'owned']);

    // Act
    $response = $this->putJson("/api/tags/{$tag->id}", ['name' => 'Hijacked']);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'Owned']);
});

// --- DELETE /api/tags/{id} (auth delete) --------------------------------

it('deletes a tag for the authenticated user', function () {
    // Arrange
    $tag = Tag::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson("/api/tags/{$tag->id}");

    // Assert
    $response->assertOk()->assertJson(['message' => 'deleted']);
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});

it('returns 404 when deleting a missing tag', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson('/api/tags/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Tag not found']);
});

it('rejects delete when unauthenticated (401)', function () {
    // Arrange
    $tag = Tag::factory()->create();

    // Act
    $response = $this->deleteJson("/api/tags/{$tag->id}");

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('tags', ['id' => $tag->id]);
});
