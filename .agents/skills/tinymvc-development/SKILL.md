---
name: tinymvc-development
description: Implement, debug, review, and test TinyMVC applications powered by TinyCore and Spark. Use for framework-specific routes, validation, authorization, models, queries, migrations, soft deletes, background jobs, and existing frontend integrations, or for explicitly requested TinyCore changes.
---

# TinyMVC Development

Develop against the project's installed TinyCore APIs and existing application conventions. The task-focused framework references live in this skill’s `references/` directory. Select the relevant topics below; do not load every reference for a small change. All source paths and commands are relative to the application root unless explicitly marked as core commands.

---

## Core Architectural Invariants (AI Agent Directives)

When writing, refactoring, or reviewing TinyMVC / Spark code, agents **must** adhere strictly to the following framework conventions:

1. **Model-First Architecture (Never `DB::table()` for models)**:
   Always use Model static methods and query builder (`User::where(...)`, `User::query()`, `User::findOrFail($id)`, `User::whereKey($id)`, `Post::create(...)`, `Post::latest()->paginate(20)`) for database operations on domain entities. **Never use `DB::table()` or `query()` when an application Model exists**. Bypassing the model drops attribute casts (`$casts`), timestamps (`created_at`/`updated_at`), soft deletes (`InteractsWithSoftDeletes`), relationships (`with()`, `whereHas()`), model events, and domain scopes.

2. **JsonResource for All API Responses**:
   Always return `[Entity]Resource::make($model)` or `[Entity]Resource::collection($paginator)` in API controllers. **Never return ad-hoc `json(['data' => ...])` or `['data' => $model]`** for resource payloads. Passing a `Paginator` to `Resource::collection()` automatically constructs the standardized `data`, `links`, and `meta` response envelope. Raw `json(['message' => '...'])` is strictly reserved for scalar status messages.

3. **Automatic Date Serialization in JsonResource**:
   **Never call `Carbon::parse($this->created_at)->toIsoUtcString()` or `$this->created_at?->toIsoUtcString()`** inside a `JsonResource`. TinyMVC's `JsonResource::normalize()` automatically converts `Spark\Carbon` instances into ISO-8601 UTC strings via `$value->toIsoUtcString()`. Model timestamps and `datetime` cast attributes are already `Spark\Carbon` instances; simply pass `'created_at' => $this->created_at`.

4. **FormRequest for Validation and Authorization**:
   Always use dedicated `FormRequest` classes (`StorePostRequest`, `UpdatePostRequest`) extending `Spark\Foundation\Http\FormRequest` for creation, update, and state-changing actions. Type-hint them in controller parameters (`public function store(StorePostRequest $request)`). TinyMVC auto-authorizes (403 `AuthorizationException`) and auto-validates (422 `ValidationException`) during constructor instantiation before the controller runs. Retrieve validated data via `$request->validated()->all()` or `$request->validated('field')`.

5. **Route Model Binding**:
   Use clean routing with parameter binding: `Route::get('/posts/{post}', [PostController::class, 'show'])` or custom field binding `Route::get('/posts/{post:slug}', ...)`. In the controller, type-hint the model parameter: `public function show(Post $post): PostResource`. The router/container automatically binds and loads the model or responds with 404.

6. **Native TinyCore ORM Relations over Raw Joins**:
   Always use native ORM relationships (`hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, `hasManyThrough`) and native relation queries (`with()`, `whereHas()`, `whereDoesntHave()`, `whereRelation()`, `withCount()`, `withExists()`, `withSum()`). Do not write manual SQL `join()` or complex `whereRaw()` queries when native relations provide the exact functionality cleanly.

7. **Thin Controllers & Clean Domain Services**:
   Keep controllers thin coordinators (inject request, model, service -> return resource). Encapsulate complex business workflows, multi-table transactions (`DB::transaction()`), external integrations, or notifications in dedicated service classes under `app/Services/<Domain>Service`.

---

## Required Coding Style

Use Laravel-style PHP coding-style/formatting with Spark's actual APIs:

- Use four spaces, never tabs. Put class and method opening braces on the next line; put control-flow and closure opening braces on the same line. Always use braces for control flow.
- Keep one statement per line. Never compress methods, guards, loops, or multiple assignments onto one line. Use blank lines between methods, properties, and distinct steps such as validation, persistence, and response construction.
- Break long query chains onto separate lines with one call per line. Expand long arrays and argument lists, with one entry per line and a trailing comma.
- Use descriptive names, explicit visibility, and parameter/return types wherever the real contract permits. Import classes at the top; keep imports organized and remove unused ones.
- Prefer guard clauses, focused methods, and direct expressions. Use arrow functions `fn ($x) => ...` for a single expression and full closures for multiple steps.
- Use Spark's native validation, resources, relations, scopes, `Arr`, `Str`, collections, helpers, and services before writing a custom replacement.

---

## Load the Relevant Guidance

Read the primary reference for the task, then follow related references only when the feature crosses those boundaries:

| Task | Reference and what it covers |
| --- | --- |
| Exact signature or method location | [API discovery](references/api-discovery.md): bounded lookup against installed source |
| Architecture and controller patterns | [Application patterns](references/application-patterns.md): thin controllers, domain services, scopes, resources |
| Routes, model binding or controller actions | [Routing and controllers](references/routing-controllers.md): groups, Route Model Binding, action injection |
| Parse or validate input | [Requests and validation](references/requests-validation.md): FormRequest lifecycle, rules, validated input |
| API output, JSON resources or redirects | [Responses](references/responses.md): JsonResource, ResourceCollection pagination, Carbon normalization, status codes |
| Models and relationships | [Models and relations](references/models-relations.md): persistence, casts, ORM relations, return contracts |
| SQL reads and writes | [Queries](references/queries.md): Model-first queries, relations vs joins, upserts, transactions, locks |
| Languages or date handling | [Localization and dates](references/localization-dates.md): Spark\Carbon datetime manipulation, auto-serialization, translations |
| Consistency, transactions or background jobs | [Advanced workflows](references/advanced-workflows.md): transactions, dispatch boundaries, locks |
| Understand boot/config or find source | [Foundation](references/foundation.md): source map, layout, bootstrap, connections |
| Request filtering and protection | [Middleware](references/middleware.md): registration, CORS, CSRF, throttling |
| Schema changes | [Migrations](references/migrations.md): schema API, pivots, generation, execution |
| Archive, restore or purge | [Soft deletes](references/soft-deletes.md): active/trash scopes, owner boundaries |
| Login, tokens and permissions | [Auth and Gate](references/auth-gate.md): guards, JWT, authorization |
| Browser state | [Sessions](references/sessions.md): handlers, cookies, flash, regeneration |
| Cache or shared locks | [Cache and locks](references/cache-locks.md): storage, expiry, atomic locks |
| Background jobs | [Queues](references/queues.md): dispatch, workers, retries, reservations |
| Templates or frontend | [Views and frontend](references/views-frontend.md): Blade, components, Vite, Inertia |
| Tests or diagnosis | [Workflow and testing](references/workflow-testing.md): feature recipe, native test runner |

---

## Verify and Hand Off

Use existing verification tools:
- Run PHP lint check on changed files: `php -l <file>`
- Run framework and application tests: `php test --filter=Name` or `composer test`
- Before handing off, review changed code to ensure:
  - No `json(['data' => ...])` in resource controllers (uses `JsonResource`).
  - No inline validation on state-changing endpoints (uses `FormRequest`).
  - No manual `Carbon::parse()->toIsoUtcString()` in resources (relies on `JsonResource::normalize()`).
  - No `DB::table()` on domain models (uses `Model::query()`, `Model::where()`).
