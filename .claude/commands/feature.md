---
description: Build or refactor a feature end-to-end with strict TDD + git workflow
---

You are implementing the following work in this repository:

> $ARGUMENTS

Follow the rules in `CLAUDE.md` exactly. Execute this loop and narrate each step briefly:

1. **Branch** — create `feature/<slug>` (new feature) or `refactor/<slug>` (refactor) off `main`.
2. **Red** — write the smallest failing Pest test that expresses the next behavior, using the AAA structure with `// Arrange / // Act / // Assert` comments. Run `./vendor/bin/pest` and show it fail.
3. **Green** — write the minimum code to pass, respecting the layered architecture (Controller → FormRequest → Service → Resource). Run `./vendor/bin/pest` until green.
4. **Refactor** — clean up while keeping tests green. Run `./vendor/bin/pint`.
5. **Repeat** 2–4 until the spec is fully covered (happy path + validation + auth + edge cases).
6. **Commit** — conventional commit(s). The pre-commit hook will run the suite; do not bypass it.
7. **PR** — as the final step, run `gh pr create` with a clear title and a body summarizing the change and how it was tested.

Constraints:
- Test first, every time. Never write implementation before a failing test exists.
- No business logic in controllers or routes. No raw models in responses.
- Stop and report if the test DB is unreachable or `gh` is not authenticated.
