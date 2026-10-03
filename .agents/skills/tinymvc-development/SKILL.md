---
name: tinymvc-development
description: Implement, debug, review, and test TinyMVC applications powered by TinyCore and Spark. Use for framework-specific routes, validation, authorization, models, queries, migrations, soft deletes, background jobs, and existing frontend integrations, or for explicitly requested TinyCore changes.
---

# TinyMVC Development

Develop against the project's installed TinyCore APIs and existing application conventions. The detailed reference is [FRAMEWORK.md](../../../FRAMEWORK.md); read the sections needed for the current task rather than loading the whole file.

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

Here `Post`, `PostResource`, and `RuntimeException` are imported classes, and `$validated` is already validated and authorized input. See [model action return values](../../../FRAMEWORK.md#model-action-return-values-tap-and-pipe) for proxy semantics and persistence caveats.

## Load the relevant guidance

Paths below are relative to the skill folder. Reference source paths in `FRAMEWORK.md` are relative to the application root.

| Work | Reference sections | Implementation to inspect when uncertain |
| --- | --- | --- |
| Endpoint or form | [Routing](../../../FRAMEWORK.md#routing), [controllers](../../../FRAMEWORK.md#controllers), [validation](../../../FRAMEWORK.md#requests-and-validation), [authorization](../../../FRAMEWORK.md#auth-and-authorization) | `Http/Routing/`, `Http/Request.php`, `Foundation/Http/FormRequest.php`, `Http/Gate.php` |
| Model or query | [Models](../../../FRAMEWORK.md#models), [relationships](../../../FRAMEWORK.md#relationships), [query builder](../../../FRAMEWORK.md#query-builder) | `Database/Model.php`, `Database/Query/`, relevant `Database/Concerns/` |
| Schema or pivot | [Migrations](../../../FRAMEWORK.md#migrations-and-schema) | `Database/Schema/`, `Database/Migration.php`, console generator/stubs |
| Archive/restore/purge | [Soft deletes](../../../FRAMEWORK.md#soft-deletes) | Soft-delete concern, `QueryBuilder.php`, `Query/BuildsWriteQueries.php` |
| Background work | [Queues](../../../FRAMEWORK.md#queue-and-jobs), [cache](../../../FRAMEWORK.md#cache), [locks](../../../FRAMEWORK.md#locks) | `Queue/`, `Cache/`, app driver config |
| Browser interface | [Views](../../../FRAMEWORK.md#views), [integrations](../../../FRAMEWORK.md#frontend-integrations), [middleware](../../../FRAMEWORK.md#middleware) | Existing templates/pages, Vite setup, installed adapter/provider |
| Framework extension | [Bootstrap](../../../FRAMEWORK.md#core-bootstrap), [providers](../../../FRAMEWORK.md#service-providers), [events](../../../FRAMEWORK.md#events), [commands](../../../FRAMEWORK.md#console-commands) | Application/container and the affected service |
| Regression | [Testing](../../../FRAMEWORK.md#testing), [verification](../../../FRAMEWORK.md#verification-checklist-for-ai-agents) | Root `test`, `tests/TestCase.php`, `tests/config.php`, relevant source methods |

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
