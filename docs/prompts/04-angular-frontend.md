# 04 — Angular frontend (separate workspace)

Create the workspace yourself first (`ng new` with the latest Angular, Tailwind, no SSR),
then paste this prompt inside it.

```
CONTEXT: This is a freshly created Angular workspace (latest version — use the modern
idioms: standalone components, signals, inject(), the @if/@for control flow, typed
reactive forms, functional interceptors). Build a frontend that consumes an existing
Laravel blog REST API. Do NOT scaffold a new workspace; work in this one. Use the Angular
CLI (`ng generate ...`) to create components/services/guards — do not hand-write their
boilerplate.

BACKEND CONTRACT
- Base URL: http://localhost:8000/api  (put it in src/environments).
- Auth: Laravel Sanctum, TOKEN based. POST /login and /register return a plaintext token.
  Send it as `Authorization: Bearer <token>` on authenticated calls. POST /logout revokes it.
- Endpoints (auth = needs Bearer):
    POST   /login                 public   -> { token, user }
    POST   /register              public   -> { token, user }
    POST   /logout                auth
    GET    /posts                 public   -> list of posts (with tags, categories, author)
    GET    /posts/{id}            public   -> single post (+ comments, likes count)
    POST   /posts                 auth     body: title, body, tags[], categories[] (ids)
    PUT    /posts/{id}            auth     (author only — 403 otherwise)
    DELETE /posts/{id}            auth     (author only — 403 otherwise)
    GET    /posts/{id}/comments   public
    POST   /posts/{id}/comments   auth     body: body
    DELETE /comments/{id}         auth     (comment author only)
    POST   /posts/{id}/like       auth     toggles like on/off
    GET    /tags                  public   READ-ONLY (no create yet)
    GET    /categories            public   READ-ONLY (no create yet)
  NOTE: the API currently returns raw Eloquent models, so exact field names may shift once
  the backend is refactored to API Resources. Keep all response shapes in ONE place
  (interfaces + a single mapping layer) so adapting later is a one-file change.

WHAT TO BUILD
1. src/environments with apiBaseUrl.
2. TypeScript interfaces: User, Post, Comment, Tag, Category (in core/models).
3. AuthService: login/register/logout; store the token (signal + localStorage); expose an
   `isAuthenticated` signal and current user.
4. A functional HttpInterceptor that attaches the Bearer token and, on 401, clears auth and
   redirects to /login.
5. Data services using HttpClient: PostService (list/get/create/update/delete + like toggle),
   CommentService (list/create/delete), TaxonomyService (getTags, getCategories).
6. Routed standalone pages:
   - /login and /register (reactive forms, validation, error display)
   - /posts  — list with title, author, tags/categories badges, likes count; link to detail
   - /posts/:id — detail with body, comments list, add-comment form (auth only), like button
   - /posts/new and /posts/:id/edit — form with title, body, multi-select of EXISTING tags
     and categories loaded from /tags and /categories (guarded: auth required)
   Use a route guard (functional CanActivate) for the authenticated routes.
   Show Edit/Delete on a post ONLY when post.author.id === current user id (ownership).
7. Minimal but clean UI with Tailwind. Loading and error states on every async view.

CONSTRAINTS
- Do NOT build any UI to create/edit/delete tags or categories — those endpoints don't exist
  yet on the backend. Only select from existing ones.
- Strongly typed everything; no `any`. HttpClient with typed responses.
- If you hit CORS from http://localhost:4200, note it in the README (the backend may need to
  publish config/cors.php) — do not work around it client-side.
- Keep it runnable: `ng serve` against the backend on :8000 with the docker stack up.

Finish by writing a short README section: how to run, the env var, and the auth flow.
```
