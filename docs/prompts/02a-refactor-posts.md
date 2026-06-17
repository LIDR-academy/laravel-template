# 02a — Session 1: Refactor Posts (governance ON)

Everything else — gather-context-first, characterization-tests-first, AAA, full-contract
assertions, the layered architecture, authorization in Policies, Artisan scaffolding, scope,
branch/PR, coverage gate — is enforced by `CLAUDE.md` + `.claude/`. The prompt is only the WHAT.

```
/feature Refactor the Posts endpoints currently in routes/api.php into the target architecture.
```

> Experiment — watch whether, with no extra instructions, it: (a) reads the inline code first,
> (b) writes characterization tests with full-shape assertions, (c) moves the duplicated
> ownership 403s into a PostPolicy unprompted, (d) leaves Comments/Likes untouched.
