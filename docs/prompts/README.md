# Workshop prompts

The big idea: **rigor lives in config, not in prompts.** Once `CLAUDE.md` + `.claude/`
(the `/feature` command + the enforcement hook) are in place, your typed prompt shrinks to
the WHAT — the agent supplies the HOW, every time, reproducibly.

This branch (`claude-setup`) ships that governance already configured. Read it, understand
why each rule exists, then use the prompts below to reproduce a similar setup in **your own**
(legacy) repo.

## 1. Bootstrap the governance in your repo

| File | What it does |
|------|--------------|
| `setup-01-claude-md.md` | Inspects your stack and writes a `CLAUDE.md` adapted to it (the rules — persuades). |
| `setup-02-feature-command-and-hook.md` | Adds the `/feature` ritual + a PreToolUse hook that blocks commits/PRs that don't meet the bar (enforces). |

Run setup-01, then setup-02, in your repository. Together they recreate governance equivalent
to what this branch carries.

## 2. Worked examples (short prompts — the HOW is already governed)

| File | Track |
|------|-------|
| `02a-refactor-posts.md` / `02b` / `02c` | Refactor a legacy resource into the layered architecture, test-first. |
| `03a-greenfield-tags.md` / `03b` | Build a new CRUD endpoint from zero, pure TDD. |
| `04b-roles-bonus.md` | Optional: add role-based authorization as a feature. |

Each prompt is one line — everything else (TDD, AAA, full-contract assertions, layers,
policies only when needed, Artisan scaffolding, scope, branch/PR, coverage gate) is enforced
by `CLAUDE.md` + `.claude/`.

## Proof it works (real run data)

Running the one-line prompts above against this repo produced, with no extra instructions:
characterization-tests-first, full-shape assertions, policies created only where ownership
exists, slugs and auth boundaries inferred from the code, and the extra `description` field on
Categories discovered by reading the migration. Two governance gaps surfaced along the way
(`git add -A` sweeping files; an always-true policy) and were closed by adding one rule each —
that is the loop: minimal prompt → run → gap → rule → lazy-proof.
