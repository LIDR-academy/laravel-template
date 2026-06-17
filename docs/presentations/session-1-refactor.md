# Session 1 — Lazy-proof AI Governance, demonstrated by refactoring legacy code (60 min)

> Paste this into Claude Code (browser) to generate a PowerPoint deck.
> Each slide: title, bullet content, and `Speaker notes`. Target balance: ~30 min theory, ~30 min live demo.
> Audience: senior engineers. The demo runs on the pre-configured `claude-setup` branch.

---

## Slide 1 — Title
- **Lazy-proof AI coding**
- Governing an agent so quality doesn't depend on the prompt
- Demonstrated by refactoring a legacy Laravel blog, test-first
- Speaker notes: The hook: with most AI coding, output quality tracks the quality of the prompt — which means it tracks the engineer's discipline that day. Today we remove that dependency. We move the rigor into config so a three-word prompt still produces tested, layered, reviewed code.

## Slide 2 — Agenda
- Part 1 — Theory (~30 min): the thesis, the safety net, the coverage trap, TDD/AAA, the governance (rule by rule), the evidence
- Part 2 — Live demo (~30 min): tour the governance, watch it act, bootstrap it in your own repo
- Speaker notes: We pause for questions between theory and demo. The demo is real, on a branch where the governance is already configured.

## Slide 3 — The problem with "just prompt better"
- AI output quality tracks prompt quality → tracks the engineer's discipline
- "Review the impacted code first", "write tests first", "assert the full shape" — meta-prompting
- Relying on the human to remember all that, every time, does not scale
- Speaker notes: Everyone has felt this: a great prompt gives great code, a lazy prompt gives lazy code. That's a fragile foundation for a team. The fix is not "train everyone to prompt perfectly." The fix is to make the discipline structural.

## Slide 4 — The thesis
- Move the rigor into **config**, not prompts
- The prompt says **WHAT**; `CLAUDE.md` + tooling say **HOW**
- Result: short prompts, reproducible quality — **lazy-proof**
- Speaker notes: This is the whole talk in one line. Everything else is evidence. By the end you'll have prompts to set this up in your own repo.

## Slide 5 — The starting point
- A legacy blog API: **all logic inline in `routes/api.php`** (fat closures, raw models)
- It works — and it ships a **green test suite** (hold that thought)
- Posts, Comments, Likes, Sanctum auth
- Speaker notes: Show the file in the demo. It works and it has tests — both will lull you into a false sense of safety.

## Slide 6 — Refactoring needs a net (and the trap)
- **Refactoring** = change structure, NOT behavior (Fowler). No net = guessing.
- The trap: the suite is green… but it only covers **POST happy paths**
- GET/PUT/DELETE, 422/401/404 → untested. Green is not safety — it's silence.
- Speaker notes: Senior insight: the danger isn't a red suite, it's a green one you misread. Both humans and AI assume "tests exist → safe to change." It's a false positive. The demo shows the green run next to a low coverage report.

## Slide 7 — Coverage is not "tests exist"
- The real bar: the **behavior matrix** per endpoint — happy · 422 · 401 · 404
- Line coverage ≥ 80% is a **backstop**, never the goal (weak assertions still pass)
- Both belong in governance, not in a prompt you might forget
- Speaker notes: This becomes a rule in CLAUDE.md and a gate in the hook. The point: encode it once, enforce it always.

## Slide 8 — TDD + AAA (the method)
- Red → Green → Refactor. For legacy: **characterization tests first** (lock current behavior), then restructure
- Every test: **Arrange / Act / Assert**, explicit, one reason to fail
- Speaker notes: AAA is communication — a senior grasps the contract in seconds. Characterization tests are how we build the net before touching untested legacy code.

## Slide 9 — The target architecture
- `Route → Controller → FormRequest → Service → Model → Resource (+ Policy)`
- Thin, single-purpose layers; authorization in Policies; responses via Resources
- Speaker notes: This is the shape every refactor and every new endpoint converges to — because the governance demands it, not because the prompt asked.

## Slide 10 — The governance, rule by rule (CLAUDE.md)
- **Get your own context first** — read impacted code, mirror patterns, scope to the task
- **Lock the full contract** — assert the whole response shape + types, not one field
- **Refactoring locks behavior first**; coverage ≥ 80% backstop
- **Scaffold via the framework CLI**; **policies only beyond auth**; **stage explicit paths, never `git add -A`**
- Speaker notes: This is the heart of the session. Walk each rule and say WHY it exists — most were added because a real run went slightly wrong without them. The CLI rule keeps stubs matching the installed framework version, not the model's training data.

## Slide 11 — Three layers of governance
- `CLAUDE.md` → the rules (**persuades**)
- `/feature` command → the repeatable ritual (branch → red → green → refactor → commit → PR)
- PreToolUse **hook** → **enforces**: commit needs green; PR needs coverage ≥ 80%
- Speaker notes: The distinction seniors love: guidance persuades, the hook guarantees. CLAUDE.md can be ignored by a model; the hook cannot — it blocks the tool call.

## Slide 11b — Why the prompt is short
- Prompt = `Refactor the Posts endpoints into the target architecture.`
- No "write tests first", no "hit 80%", no "use a Policy" — all governed
- Less prompt-engineering per task, identical quality across people
- Speaker notes: Ask the room: where's the instruction to write tests first? Answer: in CLAUDE.md and the hook. That's lazy-proof.

## Slide 12 — Evidence: same prompt, one new rule
- We ran the **same one-line prompt** before and after adding the "lock the full contract" rule
- Assertions went **61 → 196**; coverage 92% → 94%; a shape bug became catchable
- We also found two gaps live (`git add -A` swept files; an always-true policy) → fixed with one rule each
- Speaker notes: This is real run data, not a claim. The loop is: minimal prompt → run → spot the gap → add one rule → lazy-proof. The config gets better; the prompts stay tiny.

## Slide 13 — Demo plan
- 1) Tour the governance on `claude-setup` (CLAUDE.md, /feature, hook)
- 2) Expose the coverage trap
- 3) Watch the governance act on a one-line refactor prompt
- 4) Bootstrap it in your own repo
- Speaker notes: Narrate intent before each step. Keep the terminal large.

## Slide 14 — [LIVE] Tour the governance
- Open `CLAUDE.md` — read 3–4 rules and their WHY
- Open `.claude/commands/feature.md` and `.claude/hooks/guard-tests.php`
- Speaker notes: This is the centerpiece. Slow down here. Show that the rules are plain English and the hook is ~30 lines. Demystify it — they can build this today.

## Slide 15 — [LIVE] Expose the trap
- `make test` → green ✓ · `make test-coverage` → low %
- Map tested vs untested endpoints — the matrix gaps
- Speaker notes: Let the contrast land: a passing suite and a coverage report that disagree about safety.

## Slide 16 — [LIVE] Watch the governance act
- Paste the one-line prompt; observe **unprompted**: characterization tests first, layered extraction, a `PostPolicy` appears, scope stays on Posts
- Break a test, attempt `git commit` → the hook **blocks** it
- Speaker notes: The payoff. The agent does the disciplined things no one typed. The blocked commit proves enforcement is real, not advisory.

## Slide 17 — [LIVE] Bootstrap it in YOUR repo
- `setup-01-claude-md.md` → inspects your stack, writes a `CLAUDE.md`
- `setup-02-feature-command-and-hook.md` → adds the `/feature` ritual + the enforcement hook
- Works on legacy code — it reads your repo and adapts
- Speaker notes: This is the takeaway they came for. Two prompts, run in their own repository, reproduce this governance adapted to their stack and test runner. Hand them the repo link.

## Slide 18 — Recap
- AI quality should not depend on the prompt — move rigor into config
- A green suite is not coverage; refactoring preserves behavior
- `CLAUDE.md` persuades, the hook guarantees, the prompt stays tiny
- Speaker notes: Reinforce the thesis with the evidence they just watched.

## Slide 19 — Next session
- **Greenfield**: build Tags & Categories CRUD from one-line prompts — the **same governance**, a second proof
- The agent infers schema, auth, and even an extra field by reading the code
- Speaker notes: Tease that next time we create rather than refactor, and the same lazy-proof config carries it.
