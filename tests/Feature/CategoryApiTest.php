<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Feature tests for the Categories CRUD endpoints.
|
| Reads (index/show) are public; writes (store/update/destroy) require a
| Sanctum token. Categories have no owner, so authorization stops at
| authentication — there is no ownership/role boundary and no Policy.
| Responses are unwrapped (no top-level "data" envelope), matching the
| original raw-model contract of GET /api/categories.
|--------------------------------------------------------------------------
*/

// --- GET /api/categories (public list) ----------------------------------

it('lists categories ordered by name, unwrapped', function () {
    // Arrange
    Category::factory()->create(['name' => 'Zebra', 'slug' => 'zebra']);
    Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);

    // Act
    $response = $this->getJson('/api/categories');

    // Assert — full contract, no pagination envelope, ordered A→Z
    $response->assertOk()
        ->assertJsonStructure([
            '*' => ['id', 'name', 'slug', 'description', 'created_at', 'updated_at'],
        ]);

    expect($response->json())->toHaveCount(2);
    expect($response->json('0.name'))->toBe('Alpha');
    expect($response->json('1.name'))->toBe('Zebra');
});

// --- GET /api/categories/{id} (public show) -----------------------------

it('shows a single category, unwrapped', function () {
    // Arrange
    $category = Category::factory()->create([
        'name' => 'Laravel',
        'slug' => 'laravel',
        'description' => 'The PHP framework for web artisans.',
    ]);

    // Act
    $response = $this->getJson("/api/categories/{$category->id}");

    // Assert
    $response->assertOk()
        ->assertJsonStructure(['id', 'name', 'slug', 'description', 'created_at', 'updated_at']);

    expect($response->json('id'))->toBe($category->id);
    expect($response->json('name'))->toBe('Laravel');
    expect($response->json('slug'))->toBe('laravel');
    expect($response->json('description'))->toBe('The PHP framework for web artisans.');
});

it('returns 404 with a message for a missing category', function () {
    // Arrange + Act
    $response = $this->getJson('/api/categories/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Category not found']);
});

// --- POST /api/categories (auth create) ---------------------------------

it('creates a category for the authenticated user with an auto-generated slug', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/categories', [
        'name' => 'Domain Driven Design',
        'description' => 'Strategic and tactical patterns.',
    ]);

    // Assert
    $response->assertCreated()
        ->assertJsonStructure(['id', 'name', 'slug', 'description', 'created_at', 'updated_at']);

    expect($response->json('name'))->toBe('Domain Driven Design');
    expect($response->json('slug'))->toBe('domain-driven-design');
    expect($response->json('description'))->toBe('Strategic and tactical patterns.');
    $this->assertDatabaseHas('categories', [
        'name' => 'Domain Driven Design',
        'slug' => 'domain-driven-design',
        'description' => 'Strategic and tactical patterns.',
    ]);
});

it('creates a category without a description (optional, nulls out)', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/categories', ['name' => 'Testing']);

    // Assert
    $response->assertCreated();
    expect($response->json('slug'))->toBe('testing');
    expect($response->json('description'))->toBeNull();
    $this->assertDatabaseHas('categories', [
        'name' => 'Testing',
        'slug' => 'testing',
        'description' => null,
    ]);
});

it('rejects category creation with validation errors (422)', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/categories', ['name' => '']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('rejects creating a category with a duplicate name (422)', function () {
    // Arrange
    Category::factory()->create(['name' => 'PHP', 'slug' => 'php']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->postJson('/api/categories', ['name' => 'PHP']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    expect(Category::where('name', 'PHP')->count())->toBe(1);
});

it('rejects category creation when unauthenticated (401)', function () {
    // Arrange + Act
    $response = $this->postJson('/api/categories', ['name' => 'Nope']);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseMissing('categories', ['name' => 'Nope']);
});

// --- PUT /api/categories/{id} (auth update) -----------------------------

it('updates a category and regenerates its slug', function () {
    // Arrange
    $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/categories/{$category->id}", [
        'name' => 'New Name',
        'description' => 'Updated description.',
    ]);

    // Assert
    $response->assertOk()
        ->assertJsonStructure(['id', 'name', 'slug', 'description', 'created_at', 'updated_at']);

    expect($response->json('name'))->toBe('New Name');
    expect($response->json('slug'))->toBe('new-name');
    expect($response->json('description'))->toBe('Updated description.');
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'New Name',
        'slug' => 'new-name',
        'description' => 'Updated description.',
    ]);
});

it('allows updating a category while keeping its own name', function () {
    // Arrange
    $category = Category::factory()->create(['name' => 'Keep', 'slug' => 'keep']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/categories/{$category->id}", ['name' => 'Keep']);

    // Assert — the unique rule must ignore the category being updated
    $response->assertOk();
    expect($response->json('name'))->toBe('Keep');
});

it('rejects updating a category to a name already taken (422)', function () {
    // Arrange
    Category::factory()->create(['name' => 'Taken', 'slug' => 'taken']);
    $category = Category::factory()->create(['name' => 'Mine', 'slug' => 'mine']);
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/categories/{$category->id}", ['name' => 'Taken']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('returns 404 when updating a missing category', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson('/api/categories/999999', ['name' => 'X']);

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Category not found']);
});

it('rejects category update with validation errors (422)', function () {
    // Arrange
    $category = Category::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->putJson("/api/categories/{$category->id}", ['name' => '']);

    // Assert
    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('rejects category update when unauthenticated (401)', function () {
    // Arrange
    $category = Category::factory()->create(['name' => 'Owned', 'slug' => 'owned']);

    // Act
    $response = $this->putJson("/api/categories/{$category->id}", ['name' => 'Hijacked']);

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Owned']);
});

// --- DELETE /api/categories/{id} (auth delete) --------------------------

it('deletes a category for the authenticated user', function () {
    // Arrange
    $category = Category::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson("/api/categories/{$category->id}");

    // Assert
    $response->assertOk()->assertJson(['message' => 'deleted']);
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('returns 404 when deleting a missing category', function () {
    // Arrange
    Sanctum::actingAs(User::factory()->create());

    // Act
    $response = $this->deleteJson('/api/categories/999999');

    // Assert
    $response->assertNotFound()->assertJson(['message' => 'Category not found']);
});

it('rejects category delete when unauthenticated (401)', function () {
    // Arrange
    $category = Category::factory()->create();

    // Act
    $response = $this->deleteJson("/api/categories/{$category->id}");

    // Assert
    $response->assertUnauthorized();
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
