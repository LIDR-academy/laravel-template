# 04b — Roles (OPTIONAL bonus, not part of the two sessions)

Adds role-based authorization. This CHANGES behavior, so it is a feature (TDD), not a refactor.
Only run it after Session 1, on its own branch.

```
/feature Add role-based authorization for creating posts. Currently any authenticated user
can POST /posts; restrict it to users with an `author` (or `admin`) role.

- Add a `role` to users (enum: reader | author | admin; default reader) via a migration and cast.
- A reader creating a post → 403; an author/admin → 201.
- Enforce it in the PostPolicy (create), wired through the store FormRequest authorize().
- Pure TDD, full matrix: reader→403, author→201, unauthenticated→401, plus the existing
  ownership rules for update/delete stay green.
- Seed at least one author and one reader so the behavior is demonstrable.
```
