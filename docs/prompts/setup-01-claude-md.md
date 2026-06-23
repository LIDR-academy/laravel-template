# setup-01 — Bootstrap CLAUDE.md in your own repo (governance ON, run first)

Run this in YOUR repository. It inspects your stack and writes a CLAUDE.md adapted to it.
This is the "rules" layer: it persuades. (setup-02 adds the layer that enforces.)

```
Analyze THIS repository and generate a CLAUDE.md that governs how any agent (and engineer)
works here. FIRST read the codebase to detect: the language/framework and its version, how
tests are run and with which test framework, the existing architecture/layering conventions,
and the linter/formatter. Use the real commands and idioms you find — do not assume my stack.

Then write a concise CLAUDE.md encoding these rules, adapted to what you detected:

- Intro: the prompt says WHAT to build; this file says HOW. If a prompt omits process, follow
  this file by default — do not ask.
- Get your own context first: before writing anything, read the impacted code (routes/entry
  points, models, schema, the nearest existing feature). Derive schema, validation, auth
  boundaries and current behavior from the code. Mirror existing patterns. Touch ONLY the
  scope named in the task.
- Target architecture for a feature, in THIS framework's idioms: thin entry point → input
  validation → business/service layer → output shaping. Thin, single-purpose layers.
- TDD is mandatory: red → green → refactor, test first. AAA structure with explicit
  // Arrange / // Act / // Assert comments.
- Lock the full output contract: assert the COMPLETE response shape + types, not a status code
  and one field. Coverage % does not catch shape regressions; assertions do.
- Refactoring locks behavior first (characterization tests), and never changes behavior — a
  behavior change is a separate, tested feature.
- Line coverage ≥ 80% as a backstop; the real bar is the behavior matrix (happy / validation /
  auth / not-found).
- Scaffold with the framework's own CLI/generators — never hand-write boilerplate, so stubs
  match the installed version.
- Git workflow: branch first (never the default branch), conventional commits, stage explicit
  paths (NEVER `git add -A`), open the PR as the final explicit step.
- Add authorization objects (policies/guards) ONLY when authz goes beyond authentication.

Keep it specific to this repo and runnable (real test/lint commands). Do not invent rules I
didn't ask for.
```
