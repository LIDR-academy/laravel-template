# Session 2 — Greenfield Endpoints with TDD (60 min)

> Paste this into Claude Code (browser) to generate a PowerPoint deck.
> Each slide: title, bullet content, and `Speaker notes`. Target balance: ~30 min theory, ~30 min live demo.
> Audience: senior engineers. Continues directly from Session 1.

---

## Slide 1 — Title
- **Building New Endpoints with TDD**
- Tags & Categories CRUD, designed test-first
- The **same lazy-proof governance** from Session 1 — now proving itself on new code
- Speaker notes: Last time we made messy code safe and toured the governance. Today is the second proof: the exact same `CLAUDE.md` + `/feature` + hook, applied to creation instead of refactoring, from one-line prompts. If it carries both, it carries anything.

## Slide 2 — Agenda
- Part 1 — Theory (~30 min): greenfield vs brownfield, test-first design, API contract, outside-in TDD
- Part 2 — Live demo (~30 min): Tags CRUD, then Categories by pattern reuse, then PR
- Speaker notes: Note the demo will feel faster than S1 — because the architecture and rules already exist, we just fill the pattern.

## Slide 3 — Where we are
- The blog is now layered and fully tested (Session 1)
- It can attach existing tags/categories to posts — but has **no way to create them**
- That gap is today's work: full CRUD for both
- Speaker notes: This is a realistic backlog item: the data model exists, the management endpoints don't. Perfect greenfield scope on top of a clean base.

## Slide 4 — Greenfield vs Brownfield
- Brownfield (S1): behavior exists → protect it, then improve structure
- Greenfield (S2): behavior doesn't exist → tests **define** it before code
- Same TDD cycle, opposite starting point
- Speaker notes: The senior point: greenfield isn't "easier," it's a different risk. The risk is building the wrong contract. Test-first mitigates that by forcing you to specify the contract before implementing it.

## Slide 5 — Test-first = design-first
- Writing the test first forces you to answer: route, request shape, response shape, status codes, auth
- The test is the **first consumer** of your API
- Painful-to-test design is a signal, not an inconvenience
- Speaker notes: If the test is awkward to write, the API is awkward to use. TDD surfaces that before a single client integrates. That feedback loop is the real value, beyond coverage.

## Slide 6 — AAA, again (consistency matters)
- Same Arrange / Act / Assert structure as S1
- Same factories, same `RefreshDatabase`, same comment markers
- Consistency is enforced by CLAUDE.md across the whole codebase
- Speaker notes: Quick recap, don't relabor it. Emphasize that a reader can't tell who/what wrote the test — that uniformity is a feature of governing via config.

## Slide 7 — The API contract we'll drive
- `GET /api/tags`, `GET /api/tags/{id}`, `POST`, `PUT/PATCH`, `DELETE` (same for categories)
- Each resource: `name` + unique `slug`
- Validation + authz in FormRequest; responses via Resource; logic in Service
- Speaker notes: REST conventions and status codes (201 on create, 422 on validation, 404 on missing, 204 on delete). We'll assert all of these — they're the contract.

## Slide 8 — Outside-in TDD
- Start with a **Feature test** at the HTTP boundary (the user's view)
- Let failures pull the implementation into existence: route → controller → request → service → resource
- Drop to **Unit tests** for service logic (e.g., slug generation)
- Speaker notes: Outside-in keeps us building only what the endpoint actually needs — no speculative layers. The failing feature test is the to-do list; each error tells us the next file to create.

## Slide 9 — What to test (coverage that matters)
- Happy path (create/read/update/delete succeed)
- Validation errors (missing name, duplicate slug → 422)
- Auth (unauthenticated → 401)
- Not found (missing id → 404)
- Speaker notes: This four-part checklist is our definition of "covered" for a CRUD endpoint. We'll write a test for each before the code that satisfies it exists.

## Slide 10 — Reusing the pattern
- Tags first, fully test-driven
- Categories second — same shape, much faster
- Consistency comes for free because the architecture is fixed
- Speaker notes: This is the payoff of S1's discipline: the second resource is almost mechanical. Predictable structure → predictable velocity.

## Slide 11 — Governance recap
- Short prompt = WHAT; CLAUDE.md = HOW; `/feature` = the ritual; hook = enforcement
- The agent writes the failing test first because it's *required to*, not asked to
- The coverage gate (commit → green, PR → ≥ 80%) makes "we have tests" trustworthy
- Speaker notes: Same meta-lesson as S1, now applied to creation rather than refactoring. The config is the constant across both kinds of work — and the same gate that exposed the false positive in S1 now keeps our brand-new endpoints honest.

## Slide 12 — Demo plan
- 1) `/feature` for Tags → first failing Feature test (AAA)
- 2) Drive route/controller/request/service/resource to green
- 3) Add validation, auth, not-found tests → green
- 4) Categories by pattern reuse → commit → `gh pr create`
- Speaker notes: Set the rhythm: every green is a checkpoint. Keep Pest visible the whole time.

## Slide 13 — [LIVE] Red: define the contract
- Write `POST /api/tags` Feature test (Arrange user, Act post, Assert 201 + persisted)
- Run Pest → red (route doesn't exist)
- Speaker notes: Live. Read the test aloud as a spec. The red message names the missing piece — that's our next step.

## Slide 14 — [LIVE] Green: minimal implementation
- Add route, thin controller, `StoreTagRequest`, `TagService`, `TagResource`
- Re-run Pest → green
- Speaker notes: Resist over-building. Only what makes the test pass. Point out where each concern landed.

## Slide 15 — [LIVE] Expand coverage
- Add validation (duplicate slug → 422), auth (401), not-found (404), update, delete
- Red → green for each; refactor with suite green
- Speaker notes: Show the cycle tightening. Each new test is small and focused. Mention slug uniqueness as a unit-tested service concern.

## Slide 16 — [LIVE] Categories + ship it
- Repeat the pattern for Categories — notably faster
- Pint clean, suite green, commit, `gh pr create`
- Hook guarantees we never ship red
- Speaker notes: Land the velocity point live: same rigor, fraction of the time. End on the opened PR.

## Slide 17 — Recap
- Greenfield TDD: tests define the contract before code exists
- Outside-in drives only what's needed; AAA keeps it readable
- A fixed architecture + governance turns new endpoints into a repeatable pattern
- Speaker notes: Tie both sessions together: protect existing behavior (S1) and specify new behavior (S2) are two faces of the same test-first discipline.

## Slide 17b — Evidence: the prompt was one line
- `Build a full CRUD API for Tags.` / `…for Categories.`
- Unprompted, the agent inferred the schema, slug, and public-read/auth-write split
- It even **discovered the extra `description` column** on Categories by reading the migration
- Speaker notes: This is the strongest proof of "get your own context first." Nobody told it about `description`; it read the schema, validated it, persisted it, exposed it, and tested the nullable case. The governance — not the prompt — produced that.

## Slide 18 — The full picture + take it home
- Legacy → safe, layered, tested (S1) · New endpoints → test-first, enforced (S2)
- Same governance carried both — from tiny prompts
- **Take it home:** `setup-01` + `setup-02` bootstrap this in your own (legacy) repo
- Speaker notes: Close by zooming out: a disciplined backend, built and refactored under an agent that *cannot* cut corners. Then point them at the two setup prompts — that's what they run Monday on their own codebase. (An Angular frontend consuming this API is the natural next step.)
