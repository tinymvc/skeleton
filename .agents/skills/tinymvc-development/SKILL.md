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
- Generate new migrations for schema changes. `php spark make:migration --pivot` prompts for related tables and generates a file; it does not apply it. Keep the JSON migration history with its database, and inspect generated SQL/schema before running migrations in the intended environment.
- Confirm query return types and reuse rules. Start fresh builders for independent operations; do not assume a Collection fallback streams database results. The current `upsert()` takes separate conflict/update arrays; consult the query section when upgrading older calls.
- For a trash feature, read the soft-delete section before implementing the action. Choose active/all/archived rows deliberately, preserve ownership conditions, and distinguish archival from physical deletion. An explicit trash scope can permit a table-wide write.
- Preserve the installed view/SPA integration. Durable background work uses jobs; `defer()` is process-local. Follow the app's configured cache/queue drivers.

If the installed package lacks a required API, identify the gap and use a supported approach where possible. Do not silently invent the method or copy an obsolete compatibility override. A dependency upgrade or core patch should stay within the user's requested scope.

## Verify and hand off

Use existing verification tools. PHP application changes normally need `php -l` on changed files and relevant tests through `php test --filter=Name` or `composer test -- --filter=Name`. Check `tests/config.php` and the test base before relying on database isolation. Run the full suite when changing shared behavior; build frontend assets when they change.

For database work, verify stored data and affected rows, not only response status. For soft deletes, cover active/archived rows, owner boundaries, restoration, purging, and any custom column or relationship used. SQLite success does not establish MySQL/PostgreSQL parity for driver-specific behavior.

Inspect the diff for unintended changes. Report the resulting behavior, tests actually run, and concrete remaining limitations. Update affected guidance when changing a public API; keep frozen documentation versions independent. Do not treat illustrative purge, migration, worker, or external-service commands as instructions to run against live data.
