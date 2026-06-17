# setup-02 — Bootstrap the /feature command + enforcement hook (run after setup-01)

Run this in YOUR repository. This is the layer that ENFORCES (the hook), plus the repeatable
ritual (the command). CLAUDE.md persuades; the hook guarantees.

```
Create the automation that operationalizes CLAUDE.md in this repo. FIRST detect the exact
command used to run the test suite and coverage in THIS project's environment (e.g. directly,
via a task runner, or inside a container) — do not assume.

1. A slash command at `.claude/commands/feature.md` that runs the full ritual for the work
   described in $ARGUMENTS, following CLAUDE.md:
   branch → write a failing test (AAA) → minimal code respecting the architecture → green →
   refactor → conventional commit (explicit paths only) → open the PR with the GitHub CLI.

2. A PreToolUse (Bash) hook under `.claude/hooks/` that ENFORCES the Definition of Done by
   inspecting the command being run:
   - on `git commit`  → run the test suite; block (exit code 2, message on stderr) unless green.
   - on `gh pr create` → run tests WITH coverage; block unless green AND coverage ≥ 80%.
   - let every other command pass through untouched.
   Use the test/coverage command you detected, executed in this project's environment.

3. Wire the hook in `.claude/settings.json` as a PreToolUse hook matching the Bash tool.

Finally, smoke-test it: a non-commit command passes through, and a `git commit` is blocked
when a test is failing. Report what you wired and the commands you detected.
```

> Note for students whose tests need a coverage driver (PCOV/Xdebug, nyc, coverage.py, etc.):
> the hook's `gh pr create` gate needs coverage to actually run. Make sure your environment has
> the driver enabled, or the agent will tell you it can't compute coverage.
