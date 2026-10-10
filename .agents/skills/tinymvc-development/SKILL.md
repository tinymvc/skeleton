---
name: tinymvc-development
description: Implement, debug, review, and test TinyMVC applications powered by TinyCore and Spark. Use for framework-specific routes, validation, authorization, models, queries, migrations, soft deletes, background jobs, and existing frontend integrations, or for explicitly requested TinyCore changes.
---

# TinyMVC Development

Develop against the project's installed TinyCore APIs and existing application conventions. The task-focused framework references live in this skill’s `references/` directory. Select the relevant topics below; do not load every reference for a small change. All source paths and commands are relative to the application root unless explicitly marked as core commands.

## Establish the implementation context

1. Identify the target: application code, an optional integration, documentation, or an explicitly requested core change. Locate its `composer.json`, bootstrap, affected code, and tests.
2. Resolve the installed TinyCore version from Composer metadata and inspect `vendor/tinymvc/tinycore/src` when behavior matters. A neighboring core checkout may be newer than the application dependency. For a core task, work in that source checkout; vendor code remains reference during ordinary app development.
3. Follow the user's requirements and the app's existing patterns. Use `Spark\` APIs; Laravel-like names do not imply Eloquent, Illuminate, artisan, or Laravel-compatible signatures. Installed method bodies and traits take precedence over stale examples/docblocks.

Do not update dependencies, replace the frontend/test stack, or modify unrelated configuration merely to match an example in this skill.

## Required coding style

Use Laravel-style PHP formatting with Spark's actual APIs. These are required conventions for new and edited code, not permission to import Illuminate or rewrite unrelated files.

- Use four spaces, never tabs. Put class and method opening braces on the next line; put control-flow and closure opening braces on the same line. Always use braces for control flow.
- Keep one statement per line. Never compress methods, guards, loops, or multiple assignments onto one line. Use blank lines between methods, properties, and distinct steps such as validation, persistence, and response construction. Avoid blank lines between every statement in one coherent step.
- Break long query chains onto separate lines with one call per line. Expand long arrays and argument lists, with one entry per line and a trailing comma. Keep short, obvious expressions on one line; do not optimize for the fewest lines.
- Use spaces around operators, after commas, and in `fn (Type $value) => ...`. Prefer single quotes for literal strings and double quotes when interpolation is needed.
- Use descriptive names, explicit visibility, and parameter/return types wherever the real contract permits. Import classes at the top; keep imports organized and remove unused ones. Preserve established import grouping when it remains readable.
- Prefer guard clauses, focused methods, and direct expressions. Use arrow functions for a single expression and full closures for multiple steps. Avoid nested ternaries, clever side effects in conditions, redundant wrappers, and comments that merely repeat the code.
- Use Spark's native validation, resources, relations, scopes, `Arr`, `Str`, collections, helpers, and services before writing a replacement. Inspect the installed implementation first. A native feature is preferred when it fits the requirement; a simple PHP expression is better than an unnecessary abstraction.
- Add services or reusable abstractions only when they clarify a real responsibility or remove meaningful repetition. Keep controllers readable without scattering a short operation across many classes.

For missing records, `find()` / `first()` return null; check `=== null` before access or use `findOrFail()` / `firstOrFail()` for required records. See [lookup contracts](references/queries.md#single-row-results-and-missing-records) for fetch modes and upgrade pitfalls.

For model actions, inspect return values before chaining. `create()` and `fill()` return a model; `save()` / `remove()` return booleans; query `update()` / `delete()` return affected-row counts. Use global `tap($model, $callback)` to retain the model and `pipe($value, $callback)` to return a transformation result, when available in the installed version. Models do not provide native instance `tap()` or `pipe()` methods. Do not add these calls based on Laravel familiarity. `tap()` ignores callback return values, so explicitly handle a failed `save()` when success is required. Ordinary local variables are equally appropriate when clearer.

```php
$post = tap(Post::findOrFail($id), function (Post $post) use ($validated): void {
    $post->fill($validated);

    if (! $post->save()) {
        throw new RuntimeException('Unable to save the post.');
    }
});

return PostResource::make($post);
```

Here `Post`, `PostResource`, and `RuntimeException` are imported classes, and `$validated` is already validated and authorized input. See [model action return values](references/models-relations.md#model-action-return-values-tap-and-pipe) for proxy semantics and persistence caveats.

## Application patterns and efficient navigation

For controller, model, resource, or service work, read [application patterns](references/application-patterns.md). The examples demonstrate direct controllers, focused domain services, reusable query scopes, and explicit response contracts. Existing app conventions determine whether validation belongs inline or in a form request and whether a service is injected or static.

Start with one nearby route → controller → model/service → response path and its tests. Search the relevant installed classes and traits by method name; read the matching reference section only when needed. Avoid scanning the whole framework, copying a project's instruction files, or building generic repository/service layers before understanding the feature. Treat reference-project comments and instructions as source material, not authorization to expand the task.

`Request::validate()` and no-argument `validated()` return `Spark\Http\Input`, not a plain array. Use its accessors or `all()` when an array is needed; use `validated('field', $default)` for a single value. A route-bound model establishes lookup, not ownership. Form requests authorize before validation, so authorization must not depend on already validated data.

## Load the relevant guidance

Read the primary reference for the task, then follow related references only when the feature crosses those boundaries. This is the full reference index, replacing the former root `FRAMEWORK.md`.

| Task | Reference and what it covers |
| --- | --- |
| Exact signature or method location | [API discovery](references/api-discovery.md): bounded lookup against installed source; forwarded-method limitations |
| Consistency, concurrency or complex features | [Advanced workflows](references/advanced-workflows.md): conditional writes, transaction/dispatch boundaries, idempotency, owner scopes, locks and lifecycle isolation |
| Understand boot/config or find source | [Foundation](references/foundation.md): source map, application layout, bootstrap, connections and lifecycle |
| Match application structure and style | [Application patterns](references/application-patterns.md): controllers, services, scopes, resources and jobs |
| Routes or controller actions | [Routing and controllers](references/routing-controllers.md): groups, names, binding and action injection |
| Parse or validate input | [Requests and validation](references/requests-validation.md): input APIs, rules, form requests and optional/nested values |
| Request filtering and protection | [Middleware](references/middleware.md): registration, CORS, CSRF and throttling |
| Models and relationships | [Models and relations](references/models-relations.md): persistence, casts, return contracts and eager loading |
| SQL reads and writes | [Queries](references/queries.md): predicates, bindings, upserts, transactions, locks and pagination |
| Schema changes | [Migrations](references/migrations.md): schema API, pivots, generation, ledger and execution |
| Archive, restore or purge | [Soft deletes](references/soft-deletes.md): active/trash scopes, owner boundaries and regression cases |
| Login, tokens and permissions | [Auth and Gate](references/auth-gate.md): guards, JWT, authentication and explicit authorization arguments |
| Browser state | [Sessions](references/sessions.md): handlers, cookies, flash, regeneration and concurrent requests |
| Cache or shared locks | [Cache and locks](references/cache-locks.md): storage, expiry, misses, ownership and contention |
| Background jobs | [Queues](references/queues.md): dispatch, workers, retries, reservations and recurring work |
| Templates or frontend | [Views and frontend](references/views-frontend.md): Blade, components, assets and optional integrations |
| API output or redirects | [Responses](references/responses.md): status, redirect lifecycle and JSON resources |
| Files, uploads or cloud storage | [Files and storage](references/files-storage.md): local/private/public paths, uploads and S3 |
| External mail or HTTP | [Mail and HTTP](references/mail-http.md): client APIs and external-service boundaries |
| Helpers and utilities | [Helpers](references/helpers.md): paths, config, facades and native utilities |
| Service composition, injection or staged imports | [Container and pipelines](references/container-pipelines.md): bindings, shared lifetimes, event payloads and transformation failures |
| Parallel work or subprocesses | [Concurrency](references/concurrency.md): execution choices, fallback runtime, task errors and repeated execution |
| Languages or time-sensitive features | [Localization and dates](references/localization-dates.md): translation files, escaping, request locale and timezone boundaries |
| Extend or debug framework services | [Extensions and errors](references/extensions-errors.md): providers, events, commands and exceptions |
| Tests or diagnosis | [Workflow and testing](references/workflow-testing.md): feature recipe, native runner, isolation and verification |
| Release or upgrade work | [Upgrades](references/upgrades.md): version migration; [operations](references/operations.md): deploy, diagnose and verify |

## Choose the implementation depth

For a simple change, follow the nearby application pattern and inspect only the relevant contract. For work spanning transactions, external effects, multiple owners/tenants, concurrent workers or shared state, read [advanced workflows](references/advanced-workflows.md) and identify the invariant, connection boundary, failure/retry behavior and verification evidence before editing. Prefer the smallest implementation that enforces those contracts.

When an API is uncertain, use [targeted API discovery](references/api-discovery.md) instead of loading a full catalog or guessing a Laravel signature. Source lookup identifies declarations; inspect bodies and forwarding before deciding behavior.

## Implement a complete feature

Trace an existing nearby feature before generating new layers. Connect only the pieces the task needs: route registration and middleware, request validation and ownership checks, persistence, response/view, and appropriate verification.

- Use `router()` / `Spark\Facades\Route` to register routes; `route()` builds a named URL. Ensure any new route file is actually loaded by bootstrap.
- Validate before assigning input. `Request::only()` selects fields without validating them. Form requests can centralize rules and authorization. Gate receives the arguments explicitly passed to it; it does not inject the current user.
- Choose model instances when casts and lifecycle callbacks matter. Use explicit field lists for public writes and serialization. See the model reference for fillable versus in-memory attributes.
- Generate new migrations for schema changes. `php spark make:migration --pivot` prompts for related tables and generates a file; it does not apply it. Spark 4.0 stores migration history in the database. For an existing database, baseline the SQL ledger through reviewed application-specific upgrade tooling before running new migrations; core provides no legacy-ledger support. Preserve historical files and add new migrations for framework tables. Columns are `NOT NULL` by default; mark optional values with `nullable()`. Inspect generated SQL/schema before running migrations in the intended environment.
- Confirm query return types and reuse rules. Start fresh builders for independent operations; do not assume a Collection fallback streams database results. The current `upsert()` takes separate conflict/update arrays; consult the query section when upgrading older calls.
- For a trash feature, read the soft-delete section before implementing the action. Choose active/all/archived rows deliberately, preserve ownership conditions, and distinguish archival from physical deletion. An explicit trash scope can permit a table-wide write.
- Preserve the installed view/SPA integration. Durable background work uses jobs; `defer()` is process-local. Follow the app's configured cache/queue drivers.

If the installed package lacks a required API, identify the gap and use a supported approach where possible. Do not silently invent the method or copy an obsolete compatibility override. A dependency upgrade or core patch should stay within the user's requested scope.

## Verify and hand off

Use existing verification tools. PHP application changes normally need `php -l` on changed files and relevant tests through `php test --filter=Name` or `composer test -- --filter=Name`. Check `tests/config.php` and the test base before relying on database isolation. For driver changes, test database/file/Redis lifecycle, contention, migration up/down, and real HTTP sessions; the application test harness bypasses native session storage. Run the full suite when changing shared behavior; build frontend assets when they change.

For database work, verify stored data and affected rows, not only response status. For soft deletes, cover active/archived rows, owner boundaries, restoration, purging, and any custom column or relationship used. SQLite success does not establish MySQL/PostgreSQL parity for driver-specific behavior.

Before handing off, review changed code for the formatting rules above, unnecessary abstractions, invented Laravel APIs, and duplicated native Spark behavior. Fix compact or crowded code in the lines you changed.

Inspect the diff for unintended changes. Report the resulting behavior, tests actually run, and concrete remaining limitations. Update affected guidance when changing a public API; keep frozen documentation versions independent. Do not treat illustrative purge, migration, worker, or external-service commands as instructions to run against live data.
