# 02b — Session 1: Refactor Comments (governance ON)

Run after Posts. The prompt is only the WHAT; the rest is governed by `CLAUDE.md` + `.claude/`.

```
/feature Refactor the Comments endpoints currently in routes/api.php into the target architecture.
```

> Watch: does it mirror the Posts pattern, move the comment-author 403 into a CommentPolicy,
> preserve the plain-array shape of the index, and leave Posts/Likes untouched?
