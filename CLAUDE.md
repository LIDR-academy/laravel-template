# Blog API — Engineering Rules

This file governs how any agent (and any engineer) works in this repository.
The prompt you type says **WHAT** to build. This file says **HOW** it must be built.
If a prompt omits process details, follow this file by default — do not ask.

## Get your own context first

Do not rely on the prompt to hand you context — gather it yourself before writing anything:

- Read the impacted code first: the routes, the model(s) and their migrations, and the
  nearest existing feature already built in the target architecture.
- Derive the schema, validation, auth boundaries, response shape and current behavior from
  what you read. Do not ask and do not assume — go look.
- Mirror the conventions of the nearest existing feature; new code must be structurally
  indistinguishable from what is already there.
- Touch ONLY the resource/endpoints named in the task. Leave everything else unchanged.

## Stack

- Laravel 13 · PHP 8.4 · runs in Docker (see `Makefile` / `docker compose`)
- PostgreSQL (app + `laravel_test` for the test suite) · Redis
- Auth: Laravel Sanctum (token-based API auth)
- Tests: Pest 4 · coverage via PCOV — run them in the container:
  `make test` (suite) · `make test-coverage` (suite + coverage)
- Style: Laravel Pint — `docker compose exec app ./vendor/bin/pint`

## Architecture (target for every feature)

Keep layers thin and single-purpose. Data flows in one direction:

```
Route (routes/api.php)
  → Controller        // thin: no business logic, no validation rules inline
    → FormRequest      // ALL validation + authorization lives here
    → Service          // ALL business logic; the only place that mutates state
      → Eloquent Model // persistence, relationships, scopes
  → API Resource       // ALL response shaping; never return raw models/arrays
```

Rules:
- Controllers never contain validation, query building, or business rules.
- Services are plain classes under `app/Services`, injected via the container.
- Every endpoint returns an `App\Http\Resources\*` Resource, never a raw model.
- Validation + authorization go in `app/Http/Requests/*` FormRequests and `app/Policies/*`.
- Routes are grouped and named in `routes/api.php` (never logic in the route file).

## Scaffold with Artisan — never hand-write boilerplate

Always generate framework artifacts with Artisan, run inside the container:

```
docker compose exec app php artisan make:controller PostController --api
docker compose exec app php artisan make:request StorePostRequest
docker compose exec app php artisan make:resource PostResource
docker compose exec app php artisan make:policy PostPolicy --model=Post
docker compose exec app php artisan make:model Tag -mf        # model + migration + factory
docker compose exec app php artisan make:test TagApiTest --pest
```

Why: the **installed framework version is the source of truth**. Artisan stubs match this
version's conventions and signatures, so we never ship outdated or incompatible patterns
from memory. Hand-written boilerplate drifts from the framework — let the CLI generate the
skeleton, then fill in the logic. (The Angular frontend follows the same rule with
`ng generate`.)

## Testing — TDD is mandatory

Red → Green → Refactor. **Write the failing test first**, always.

- Every test uses the **AAA** structure with explicit comments:
  ```php
  it('creates a tag', function () {
      // Arrange
      $user = User::factory()->create();

      // Act
      $response = $this->actingAs($user)->postJson('/api/tags', ['name' => 'Laravel']);

      // Assert
      $response->assertCreated();
      expect(Tag::where('name', 'Laravel')->exists())->toBeTrue();
  });
  ```
- Endpoint behavior → **Feature tests** (`tests/Feature`). Services/units → **Unit tests** (`tests/Unit`).
- Use `RefreshDatabase` and model factories. No fixtures, no hitting real services.

### Lock the full response contract

When testing an endpoint — especially characterization tests for a refactor — assert the
COMPLETE response shape, not just a status code and one field:

- Assert EVERY key the endpoint returns with `assertJsonStructure([...])` (including nested
  relations and counts), so a Resource that drops or renames a field fails the test.
- Assert values for stable fields (ids, titles, flags) and their TYPE where it matters
  (e.g. `published` must be a boolean), plus DB side effects.
- A single `assertJsonFragment(['title' => ...])` is NOT enough — coverage % does not catch
  a shape regression; only assertions do.
- Avoid `assertExactJson` when the payload has timestamps or random ids (brittle); assert
  the key set + stable values instead.

### Refactoring locks behavior first

When the task is to refactor existing behavior:

1. FIRST write characterization tests that lock the CURRENT observable behavior (the full
   contract — see above) and run them green.
2. THEN restructure into the target architecture, keeping every test green at each step.

Refactoring never changes behavior. A behavior change is a separate feature with its own test
(e.g. introducing roles, or tightening who may create a resource).

### Coverage is not "tests exist"

A green suite proves nothing about what is NOT tested. For every endpoint, the
Definition of Done requires the full behavior matrix:

- happy path
- validation errors (422)
- auth failure (401)
- not found (404)

Line coverage must be **≥ 80%** (`make test-coverage`). Treat coverage as a backstop,
never a substitute for the behavior matrix — high coverage with weak assertions is still
a gap.

## Git workflow

- Never commit to `main`. Branch first: `feature/<kebab-slug>` or `refactor/<kebab-slug>`.
- Conventional commits: `feat:`, `refactor:`, `test:`, `fix:`, `chore:`.
- Run Pint before committing. Tests must be green before every commit.
- Open the PR as the **final, explicit step** with `gh pr create` (fill title + body).

**Enforced by the pre-commit hook** (`.claude/hooks/guard-tests.php`):
- `git commit` → blocked unless the suite is **green**.
- `gh pr create` → blocked unless the suite is green **and coverage ≥ 80%**.

## Definition of Done

1. Failing test written first, then implementation (TDD).
2. Layers respected (Controller / FormRequest / Service / Resource).
3. Behavior matrix covered (happy / 422 / 401 / 404); coverage ≥ 80%.
4. `make test` green · Pint clean.
5. On a feature/refactor branch, conventional commits, PR opened with `gh pr create`.

For full features, prefer the `/feature` command, which runs this loop end to end.
