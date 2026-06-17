# 00 — Seed the legacy blog (one-off, governance OFF)

Run this BEFORE adding any governance. It intentionally produces bad code.

```
Build a blog REST API as INTENTIONAL LEGACY code for a refactoring exercise.
IGNORE CLAUDE.md completely: put ALL logic inline in routes/api.php — no controllers,
no form requests, no services, no API resources, NO tests. Fat route closures,
inline $request->validate(), inline queries, return raw models/arrays. Make it messy
but functional.

Domain: Post, Comment, Like, Tag, Category + migrations and factories.
- Sanctum login route that issues a token; protect write routes with auth:sanctum.
- Posts CRUD; a post can attach EXISTING tags and categories via pivot tables.
- Update/Delete posts: only the author (post.user_id === request user) may do it, else 403.
- Comments on posts (create/list/delete; only the comment author may delete) and Likes (toggle).
- IMPORTANT: do NOT add any endpoint to create/update/delete Tags or Categories.
- Seeders: a few users, ~5 categories, ~10 tags, ~8 posts with comments and likes.

This is the starting baseline. Commit it on its branch as "legacy blog baseline".
```
