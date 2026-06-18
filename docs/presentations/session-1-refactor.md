# Session 1 — Refactoring Legacy Laravel with TDD (60 min)

> Paste this into Claude Code (browser) to generate a PowerPoint deck.
> Each slide: title, bullet content, and `Speaker notes`. Target balance: ~30 min theory, ~30 min live demo.
> Audience: senior engineers. Tone: practical, opinionated.

---

## Slide 1 — Title

- **Refactoring Legacy Code with TDD**
- Turning an inline-routes blog API into a layered, tested design
- Live coding with an AI agent under guardrails
- Speaker notes: Set expectations — this is not "AI writes code for us." It's "we govern an agent to apply disciplined engineering." Today: take a deliberately messy Laravel blog and refactor it safely, test-first.

## Slide 2 — Agenda

- Part 1 — Theory (~30 min): refactoring, the safety net, the coverage trap, TDD + AAA, target architecture, governing the agent
- Part 2 — Live demo (~30 min): expose the coverage gap, then refactor Posts → Comments → Likes
- Q&A throughout
- Speaker notes: We pause for questions between theory and demo. The demo is real — failures and a blocked commit are part of the lesson.

## Slide 3 — The starting point

- A working blog API where **all logic lives in `routes/api.php`**
- Fat closures: inline validation, inline queries, raw models returned
- Auth (Sanctum), Posts, Comments, Likes — heavy duplication
- And… it ships with a **green test suite** (remember that)
- Speaker notes: Show the file later. Stress: it *works*, and it even *has tests*. Both facts will lull you into a false sense of safety. Hold that thought — we'll come back to those tests.

## Slide 4 — Refactoring: definition

- **Refactoring** = changing internal structure **without changing external behavior** (Fowler)
- It is NOT a rewrite, NOT new features, NOT bug fixing
- Goal: same API responses, better internals
- Speaker notes: The discipline is behavior preservation. If observable behavior changes, that's not a refactor — it's a change, and it needs its own test. Keep the two activities separate.

## Slide 5 — The #1 rule: never refactor without a net

- Untested refactoring = guessing
- You need a **safety net** that fails the moment behavior drifts
- "We already have tests" — do we, though?
- Speaker notes: This sets up the trap on the next slide. The instinct is "great, there's a suite, I'm covered." Senior engineers know to ask the next question: covered *for what*?

## Slide 6 — The trap: a green suite ≠ safety

- The existing suite tests **only the POST/create happy paths**
- GET, PUT/PATCH, DELETE → untested · 422 / 401 / 404 → untested
- Green ✓ — but most behavior is unprotected
- Refactor a GET endpoint now and nothing fails. That's not safety, that's silence.
- Speaker notes: This is the heart of the session. The demo shows the green run side-by-side with a low coverage report and a behavior matrix full of gaps. The danger isn't a red suite — it's a green one you misread. Both humans and AI assume "tests exist → safe to change." It's a false positive.

## Slide 7 — Coverage is not "tests exist"

- For every endpoint, the bar is the full **behavior matrix**: happy · 422 · 401 · 404
- Line coverage must be **≥ 80%** — a backstop, never the goal
- High coverage with weak assertions is *still* a gap
- We encode this in `CLAUDE.md`; the hook enforces: **commit → green**, **PR → coverage ≥ 80%**
- Speaker notes: The fix is twofold — a behavioral checklist (what must be tested) and a coverage floor (a numeric backstop). Neither alone is enough. We move this from "hope the dev remembers" to "the system refuses to ship otherwise."

## Slide 8 — Characterization tests (golden master)

- Tests that **capture current behavior as-is** — even quirks and bugs
- You don't assert what *should* happen; you assert what *does*
- Written against the public API (HTTP), not internals
- This is how we fill the gaps before touching the code
- Speaker notes: We lock behavior at the *boundary* (the HTTP response), so we're free to rearrange everything behind it. The POST-only suite isn't a net yet — we extend it into one, endpoint by endpoint, before refactoring each.

## Slide 9 — The TDD cycle

- **Red** → failing test · **Green** → minimum code · **Refactor** → clean up, stay green
- In legacy work: characterization tests go green first, then we refactor under them
- Speaker notes: Greenfield (next session) uses Red to drive new behavior. Legacy refactoring spends its time in the Refactor step, re-running tests constantly.

## Slide 10 — AAA: the anatomy of a readable test

- **Arrange** — set up state (factories, auth)
- **Act** — one action under test (the HTTP call)
- **Assert** — verify the outcome
- One reason to fail per test; explicit `// Arrange / // Act / // Assert`
- Speaker notes: AAA is communication. A senior should grasp the contract in seconds. We enforce the comment markers in CLAUDE.md so every test reads the same.

## Slide 11 — Target architecture

- `Route → Controller → FormRequest → Service → Model → Resource`
- Controller: thin orchestration · FormRequest: validation + authz
- Service: business logic (only place that mutates) · Resource: response shaping
- Speaker notes: Walk the arrow. Each box has one reason to change. Contrast with the "god closure" where all four concerns are tangled. This separation is what makes the code unit-testable, not just end-to-end.

## Slide 12 — Before / After (same behavior)

- Before: 60-line route closure doing validate + query + transform + respond
- After: small, named, individually testable units
- Same JSON out — proven by the characterization tests
- Speaker notes: The suite is identical before and after. That's the proof the refactor is behavior-preserving.

## Slide 13 — Governing the agent (the meta-lesson)

- Rigor lives in **config, not prompts**
- `CLAUDE.md` = the HOW (architecture, TDD, AAA, coverage rules)
- `/feature` command = the ritual (red → green → refactor → branch → PR)
- Hook = **enforcement**: commit needs green; PR needs coverage ≥ 80%
- Speaker notes: Most teams miss this. A short prompt produces disciplined code only because the discipline is encoded once, in config, applied every time. Guidance (CLAUDE.md) persuades; the hook guarantees. The coverage gate is what would have caught our POST-only trap automatically.

## Slide 14 — Why the prompt is short

- The prompt says only **WHAT**: "refactor Posts/Comments/Likes into the target architecture"
- The HOW (tests-first, layers, coverage, branch, PR) is already governed
- Result: reproducible quality, less prompt-engineering per task
- Speaker notes: Show the one-paragraph prompt. Ask: "Where's the instruction to write tests first or hit 80%?" Answer: in CLAUDE.md and the hook. That's the point.

## Slide 15 — Our refactor strategy

- One resource at a time: **Posts → Comments → Likes**
- Per resource: extend characterization tests to the full matrix → extract FormRequest → Service → Resource → thin controller
- Keep the suite green after every extraction; commit per resource
- Speaker notes: Incremental and reversible. If anything goes red, stop and fix before moving on. Small commits = easy rollback = safe demo.

## Slide 16 — Demo plan

- 1. Run the suite (green) and the coverage report (low) — expose the trap
- 1. Extend characterization tests for Posts to the full matrix (AAA)
- 1. Extract layers; suite stays green
- 1. Try a bad commit → watch the hook block it
- Speaker notes: Narrate intent before each step. The coverage reveal and the hook block are intentional theatre — show the false positive and the enforcement are both real.

## Slide 17 — [LIVE] Expose the trap

- `make test` → green ✓ · `make test-coverage` → low %
- Map tested vs untested endpoints on screen — the matrix gaps
- "If we trusted the green check, we'd refactor blind"
- Speaker notes: Live. Let the contrast land: a passing suite and a coverage report that disagree about safety. This is the emotional hook of the session.

## Slide 18 — [LIVE] Lock behavior, then refactor

- Add Feature tests that assert current Post endpoints' responses (fill the matrix)
- Run suite → green (behavior now captured)
- Extract `StorePostRequest`, `PostService`, `PostResource`; thin the controller
- Speaker notes: Live. Re-run after each extraction. Talk through what each layer owns. If something breaks, that's the net doing its job — fix and continue.

## Slide 19 — [LIVE] Enforcement in action

- Introduce a deliberate failure, attempt `git commit` → hook blocks it
- Fix, commit cleanly, then `gh pr create` → coverage gate runs
- Speaker notes: Close the loop: the agent literally cannot publish broken or under-covered work. Show the commit-green vs PR-80% distinction live.

## Slide 20 — Recap

- Refactoring = behavior-preserving structural change
- A green suite is not coverage — beware the false positive
- No refactor without a real net → characterization tests + the behavior matrix
- Config (CLAUDE.md + command + hook) makes the agent disciplined by default
- Speaker notes: Reinforce the through-line: discipline is systematized, not improvised — and the coverage gate is what makes "we have tests" trustworthy.

## Slide 21 — Next session

- **Greenfield**: build Tags & Categories CRUD from zero, pure TDD
- We reuse the exact architecture, rules, and coverage gate we just established
- Speaker notes: Tease that next time the tests come *first* and *drive design* — a different muscle than today's "lock then refactor."

