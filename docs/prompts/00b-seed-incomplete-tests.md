# 00b — Seed the intentionally incomplete test suite (one-off, governance OFF)

The "tests ≠ coverage" false-positive artifact. Run on the legacy branch, governance OFF.

```
On the legacy blog, add an INTENTIONALLY INCOMPLETE test suite as a teaching artifact about
false confidence. Do NOT follow CLAUDE.md (it is not active here).

Write Pest Feature tests that cover ONLY the POST / create endpoints:
- POST /api/login            (happy path)
- POST /api/posts            (authenticated create, happy path)
- POST /api/posts/{id}/comments  (authenticated create)
- POST /api/posts/{id}/like  (like toggle)

Rules for these seeded tests:
- They MUST pass (green suite) — use RefreshDatabase + factories.
- Cover ONLY the happy path of those POSTs. Do NOT test any GET/index/show, PUT/PATCH,
  DELETE, validation errors (422), auth failures (401), or not-found (404).
- Keep assertions shallow (assert status + that a row was created) so even the "covered"
  endpoints are weakly verified.
- No tests for tags/categories.

Goal: a green suite that LOOKS reassuring but leaves most behavior unprotected — the
"we have tests, so refactoring is safe" trap. End with a short table: endpoint + verb → tested?
```
