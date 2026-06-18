# Workshop prompts

Short prompts for each Claude Code session. The rigor (TDD, AAA, architecture, branch/PR,
coverage gate, full-contract assertions, Artisan CLI usage) lives in `CLAUDE.md` + `.claude/`
— not in these prompts.

## Execution order

| # | File | Where it runs | Governance |
|---|------|---------------|------------|
| 00 | `00-seed-legacy.md` | one-off, before the workshop | OFF (intentional legacy) |
| 00b | `00b-seed-incomplete-tests.md` | one-off, before the workshop | OFF |
| 01 | `01-fix-coverage-driver.md` | one-off, infra | OFF |
| 02a | `02a-refactor-posts.md` | **Session 1**, off `claude-setup` | ON |
| 02b | `02b-refactor-comments.md` | **Session 1**, off the Posts branch | ON |
| 02c | `02c-refactor-likes.md` | **Session 1**, off the Comments branch | ON |
| 03a | `03a-greenfield-tags.md` | **Session 2**, off the refactored blog | ON |
| 03b | `03b-greenfield-categories.md` | **Session 2**, off the Tags branch | ON |
| 04 | `04-angular-frontend.md` | separate Angular workspace | n/a |
| 04b | `04b-roles-bonus.md` | optional bonus, own branch | ON |

Both tracks are split so each prompt runs in its own focused Claude Code session, building
cumulatively on the previous branch (the `/feature` command creates the child branch).

- Session 1 (refactor): Posts → Comments → Likes.
- Session 2 (greenfield): Tags → Categories. Tags is full TDD from scratch; Categories reuses
  the pattern (but has an extra `description` field — not a blind copy).

Branches: `main` (clean) → `demo-refactor` (point-0 baseline) → `claude-setup` (baseline + governance).
Prompts 00, 00b and 01 produced the point-0 baseline already committed on `demo-refactor`.

See `../MENTOR-GUIDE.md` for the full facilitator runbook.
