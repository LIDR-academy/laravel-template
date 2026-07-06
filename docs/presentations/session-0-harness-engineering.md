# Session 0 — Harness Engineering: the layer above the prompt (~50 min, conceptual)

> The conceptual foundation for the two hands-on sessions. It names what this workshop
> actually is — **harness engineering** — and gives the vocabulary (rings, guides/sensors,
> feedforward/feedback) that Sessions 1 and 2 then demonstrate live.
> Each slide: title, bullets, `Speaker notes`. A Spanish, LIDR-branded pptx version of this
> deck lives in `spec_deck_harness_engineering.md`.

---

## Slide 0 — Title
- **Harness Engineering**
- The layer that sits above the prompt
- Demonstrated on a legacy Laravel blog, refactored and extended test-first
- Speaker notes: "Harness engineering" is the term the market is converging on in 2026. It means two different things at once and confuses everyone. You leave here able to place each one — and knowing why your quality with AI doesn't have to depend on the prompt you happened to write today.

## Slide 1 — The problem: a good prompt isn't enough
- LLMs are **non-deterministic**: the same prompt yields different results
- They don't know your context or conventions by default
- They "think" in tokens; they don't understand your code the way you do
- Quality can't depend on how inspired you are when you type the prompt
- Speaker notes: This is where most teams stall — "better prompt, better model." The ceiling on autonomy isn't only the model; it's the system around the model. That system has a name.

## Slide 2 — The broad definition: Agent = Model + Harness
- **Harness** = everything in an agent **except the model**
- Term popularized by Mitchell Hashimoto (Feb 2026), naming what people already built informally
- The definition is **deliberately broad** → which is why it sounds like two different things
- That breadth is the source of the confusion; the rest of this deck orders it
- Speaker notes: Hook (verify before quoting live): a small team reportedly produced a very large codebase mostly via "harness engineering" rather than hand-typed code. Use only if you can confirm it. The durable point: the harness, not the keystrokes, is where the engineering moved.

## Slide 3 — One term, three rings
- **User harness** (outer) — what YOU configure: `CLAUDE.md` · `GEMINI.md` · `AGENTS.md` · hooks · skills · MCP
- **Builder harness** (middle) — the tool: Claude Code · Cursor · Windsurf · Antigravity
- **Model (LLM)** (core) — the thing being "harnessed"
- The question that disambiguates everything: **which ring are you in?**
- Speaker notes: Concentric-rings model (Birgitta Böckeler, Thoughtworks). These aren't competing definitions — it's the same word applied to nested rings. Everything we do in this workshop is in the OUTER ring.

## Slide 4 — Builder harness — the tool
- Built by the people who make the tool
- Claude Code, Cursor, Windsurf, Antigravity **are** harnesses at this layer
- They wrap the model with: tools, execution loops, sandbox, memory, permissions
- You could swap the model inside; the engineering value lives in the harness
- When you build OpenCode, you do harness engineering **in this ring**
- Speaker notes: This answers the first half of the usual confusion — "why do they call Claude Code / Cursor a harness?" Because they are one.

## Slide 5 — User harness — what YOU configure
- You configure it, on top of an existing tool
- Here live: **CLAUDE.md · GEMINI.md · AGENTS.md**, hooks, skills, commands, MCP
- Part of the harness is built in (system prompt, code retrieval)…
- …but the user features let you build a harness tailored to your case
- When you write a `CLAUDE.md`, you do harness engineering **in this ring**
- Speaker notes: Second half of the confusion resolved. This `laravel-template`'s `CLAUDE.md` plus its hook is exactly this — a user harness.

## Slide 6 — How you build a user harness (Böckeler's matrix)
- **Axes:** rows = *Guides (feedforward)* / *Sensors (feedback)* · columns = *Computational (deterministic)* / *Inferential (semantic)*
- **Guides × Computational:** scaffolding with Artisan, generators, Pint
- **Guides × Inferential:** rules in `CLAUDE.md`, architecture, the TDD mandate, examples
- **Sensors × Computational:** tests, linters, type-checkers, the pre-commit hook
- **Sensors × Inferential:** "LLM as judge", semantic AI code review
- You need **both directions**: guides that anticipate + sensors that correct
- Speaker notes: Guides = anticipate before acting (raise the probability). Sensors = observe after (allow self-correction). Computational = fast, deterministic, runs on CPU. Inferential = semantic, non-deterministic, pricier. Feedforward-only and the agent never learns if it hit; feedback-only and it repeats the same mistake.

## Slide 7 — Case: the anatomy of a CLAUDE.md
- The prompt says the **WHAT**; the file says the **HOW**
- If the prompt omits the process, the file is followed by default — no asking
- Architecture rules + TDD mandate + AAA + full-contract assertions + DoD = **inferential guides**
- The `guard-tests.php` hook (blocks commit if the suite isn't green; blocks PR if coverage < 80%) = **computational sensor**
- The design key: it **combines both directions** (feedforward + feedback)
- Speaker notes: This is the "aha": the file works even when the prompt is weak — not by magic, but because it pairs prose that guides with a hook that forces. Coverage runs via PCOV; the gate is real, not advisory.

## Slide 8 — The precision on "deterministic"
- Most of the `CLAUDE.md` is **not** deterministic
- The prose (the rules) is **inferential**: it raises the probability, it doesn't guarantee
- The truly deterministic part is the **computational layer**: the hook, Artisan, Pint
- In one line: **the markdown lifts; the hooks enforce**
- Speaker notes: Important precision for teaching it well. Many sell the `CLAUDE.md` as "deterministic config"; it isn't. Determinism enters through the computational sensors, not through the text.

## Slide 9 — Three nested levels
- **Prompt engineering** — writing the instruction
- **Context engineering** — managing what the model sees and when
- **Harness engineering** — the whole control system around it
- They're **nested**: building a user harness *is* a form of context engineering
- Speaker notes: Closes the mental model. They don't replace each other; they stack. The prompt still matters — it just stops being the only control point.

## Slide 10 — In your case — B2B / B2C
- **B2B (client team):** lands on the client repo's `CLAUDE.md`; the **AI Champion** maintains guides + hooks; consistency across devs without depending on each person's prompt
- **B2C (individual learner):** start from a **template** (this `laravel-template`); a `CLAUDE.md` + a minimal hook; portable to any project of yours
- Common line: it's not about writing better prompts; it's building **the right ring once**
- Speaker notes: The one branched slide. For a client team, emphasize the Champion role; for an individual, emphasize portability.

## Slide 11 — Close + what's next
- Builder = you build the tool. User = you configure the existing one. Prompt → Context → Harness.
- **Next (S1):** we watch a user harness refactor legacy code, test-first, from a one-line prompt
- **Then (S2):** the same harness builds new endpoints from scratch
- Speaker notes: The word was defined broad on purpose ("everything but the model"); each ring inherits the name. The disambiguating question is always: which ring are you in? Now let's watch the outer ring do real work.
