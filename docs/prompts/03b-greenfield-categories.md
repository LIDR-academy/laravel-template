# 03b — Session 2: Greenfield Categories CRUD (governance ON)

Run after Tags. The prompt is only the WHAT; the rest is governed by `CLAUDE.md` + `.claude/`.

```
/feature Build a full CRUD API for Categories.
```

> Watch the key signal: does it discover the extra nullable `description` column on its own
> (by reading the migration) and validate/persist/expose it — without being told? That single
> field is the test of whether "gather your own context" really replaces a verbose prompt.
