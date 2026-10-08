# Workflow Testing

Contents:

- [How To Use This File](#how-to-use-this-file)
- [AI Agent Decision Rules](#ai-agent-decision-rules)
- [Common Feature Recipe](#common-feature-recipe)
- [Common Mistakes To Avoid](#common-mistakes-to-avoid)
- [Testing](#testing)
- [Verification Checklist For AI Agents](#verification-checklist-for-ai-agents)
- [Diagnose before changing behavior](#diagnose-before-changing-behavior)
- [Core checkout verification](#core-checkout-verification)

Read this reference for how to use this file, ai agent decision rules, common feature recipe, common mistakes to avoid, testing, verification checklist for ai agents. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## How To Use This File

1. Identify whether the user is changing an application, TinyCore, an optional integration, or documentation. Follow the existing app's choices and the user's requested scope.
2. Inspect the relevant entry points: `composer.json`, installed package version, `bootstrap/app.php`, the matching routes/controller/model, and nearby tests. Check `package.json` only when frontend work is involved. Read only the configuration needed for the task; do not dump `.env` secrets.
3. Choose the matching references in [the skill](../SKILL.md). For uncertain signatures or side effects, inspect the installed method body, its traits, and the app's own wrappers. Method names resembling Laravel are not evidence of identical behavior.
4. Implement a complete path through the relevant layers: route and middleware, input validation/authorization, persistence, response/view, and a focused regression when the behavior warrants it. Do not generate unused layers or change the frontend stack by default.
5. Validate against the configured test environment. Report the behavior changed, checks run, and any dependency or driver limitation that remains.

This file is framework guidance, not a replacement for the user's task. Commands below describe development workflows; examples of migrations, purges, workers, and external services are not instructions to execute them on live data. Generate and review the necessary code first, and use the intended test environment for verification.

## AI Agent Decision Rules

Use this file to choose the right framework APIs; use the installed implementation and existing app behavior to resolve version differences. Update only the relevant guidance when behavior changes.

Prefer these choices:

- Routes: use `Spark\Facades\Route` when the app imports it, otherwise use `router()`.
- Controllers: return arrays for JSON APIs, `json()` for explicit status codes, and `response()` for plain responses.
- Validation: use `Spark\Foundation\Http\FormRequest` for reusable request validation, or `$request->validate()` for simple cases.
- Database: use `Spark\Database\Model` or `query($table)` before raw SQL.
- Background work: use class jobs with `Spark\Queue\Dispatchable`; use `dispatchOnce()` for recurring scheduler/cron jobs.
- Paths: use config and helpers such as `storage_dir()`, `root_dir()`, `upload_dir()`, and `views_dir()`.
- Framework uncertainty: inspect the matching source file under `./vendor/tinymvc/tinycore/src/` before guessing Laravel behavior.

## Common Feature Recipe

For a new API resource, use only the pieces the feature needs; reuse existing schema, models and requests where suitable:

1. Create migration in `database/migrations`.
2. Create model in `app/Models`.
3. Create request class in `app/Http/Requests` if validation is more than trivial.
4. Create controller in `app/Http/Controllers/Api`.
5. Add routes in `routes/api.php`.
6. Add middleware only if needed.
7. Return arrays or `json()` responses for APIs.
8. Use model/query builder APIs, not raw SQL.

Example:

```php
// routes/api.php
use App\Http\Controllers\Api\PostController;
use Spark\Facades\Route;

Route::get('/posts', [PostController::class, 'index']);
Route::post('/posts', [PostController::class, 'store']);
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::put('/posts/{id}', [PostController::class, 'update']);
Route::delete('/posts/{id}', [PostController::class, 'destroy']);
```

## Common Mistakes To Avoid

- Do not import Laravel's route facade. Use `Spark\Facades\Route` or `router()` depending on the app style.
- Do not import `Illuminate\\*` classes.
- Do not create Laravel `FormRequest`, `Middleware`, `Migration`, or `Model` classes.
- Do not use `artisan`; TinyMVC has its own console command system.
- Do not assume Eloquent relationship syntax is identical. Inspect existing models.
- Do not edit framework/vendor files in an app unless asked.
- Do not bypass config with hardcoded storage paths.
- Do not use raw `$_POST`/`$_GET` in controllers when `Request` helpers are available.
- Do not run non-OPTIONS controller logic for CORS preflight.
- Do not send `Access-Control-Allow-Credentials: false`.
- Do not mix queue config with cache config.
- Do not use Laravel queue APIs such as `onConnection()` unless the app has added its own compatibility layer.

## Testing

TinyMVC includes a dependency-free plain PHP runner. Run `php test` or
`composer test`. Options: `--testsuite Unit|Feature`, `--filter text`, and
`--list-tests`; pass options to Composer after `--`.

- Unit tests: `tests/Unit/*Test.php`, extending `Spark\Testing\TestCase`.
- Application feature tests: `tests/Feature/*Test.php`, extending `Tests\TestCase`.
- Feature lifecycle: `Spark\Testing\ApplicationTestCase` creates a fresh app.
- Response assertions: `Spark\Testing\TestResponse` wraps `Spark\Http\Response`.
- Entry point and test config: the root `test` runner, `tests/TestCase.php`, and `tests/config.php`; follow any custom bootstrap present in the app.

Tests are public non-static `test*` methods without arguments. Use strict
assertions such as `assertSame`, `assertTrue`, `assertCount`, and `assertArrayHasKey`.
The runner supports setup/teardown, expected exception class/message/code,
`assertThrows`, and explicit `markTestSkipped`. Failures, warnings,
and empty test selections produce non-zero exits.

Feature helpers include `get`, `post`, `getJson`, `postJson`, and
`request($method, $uri, $data, $headers, json: true)`. Responses support
`assertOk`, `assertStatus`, `assertSee`, `assertHeader`, `assertRedirect`, and
`assertJson` (complete JSON equality with strict types), `assertJsonPath`,
`assertJsonFragment`, `assertJsonStructure`, `assertJsonCount`, and
`assertJsonValidationErrors`. Common HTTP verbs also have named helpers, including
`put`, `patch`, `delete`, `options`, `head`, `putJson`, `patchJson`, and `deleteJson`.
Use `withHeaders`, `withToken`, `withSession`, `withCookies`, and `actingAs` for
request state. `assertDatabaseHas`, `assertDatabaseMissing`, and
`assertDatabaseCount` inspect the configured test database. Application tests live
in the skeleton's `tests/`. In a TinyCore source checkout, use `php tests/run.php --filter SoftDeleteTest` or
`php tests/database.php --filter SoftDeleteTest` for the three-engine matrix.

`APP_ENV=testing` must be set before creating a CLI application. In that mode,
`.env` and config caches are skipped; `Application::create()` merges
`tests/config.php` before provider registration. The feature base supplies a
unique per-test storage path under `storage/framework/testing` in the skeleton; the supplied config uses in-memory SQLite. Override `testStorageDirectory()` before application boot to choose the parent directory. Core defaults to the system temporary directory. Normal cleanup removes only the current test’s directory, including after failures, and preserves the shared parent and `.gitignore`. Custom teardown must call its parent in `finally`. After forced termination, remove abandoned directories only when no tests are running. Middleware,
including CSRF, remains active. Unexpected exceptions reach the runner; early
responses, redirects, aborts, and validation errors are captured. Deferred work
runs after each successful request without flushing the runner's buffers.

Use the built-in assertions and small PHP stub objects by default. Do not introduce another test runner or Laravel-specific test traits just to write a regression; honor an existing app test stack or an explicit user request to change it.

## Verification Checklist For AI Agents

Before finishing changes in a TinyMVC app:

1. Run `php -l` on every changed PHP file.
2. Check route/controller namespaces match the app.
3. Check middleware aliases exist in `bootstrap/middlewares.php`.
4. Check config keys and APIs against the installed framework and app overrides.
5. If changing DB code, verify schema/model names, return types, and active/archive/owner boundaries using an isolated database.
6. If changing CORS/CSRF/throttle, test normal request and preflight/invalid cases when possible.
7. For drivers, run file/SQLite and real Redis tests, contention tests, native HTTP session tests, and migrations up/down. Run MySQL/PostgreSQL integration tests on the exact production versions before release.
8. Run `git diff --check`.
9. Run relevant tests with `php test --filter=Name` or `composer test -- --filter=Name`; run the full suite for shared behavior changes. Run `npm run build` when frontend assets change.
10. Mention anything not tested.


## Diagnose before changing behavior

Reproduce the smallest failing request or operation using the existing test harness. Follow its route registration, middleware order, action, model/query, and response path. Inspect the exception and first relevant application/framework frame; do not hide unexpected exceptions with a catch-all response.

For database failures, record the driver, bindings, schema, returned rows and persisted state. Distinguish a stale builder, missing scope, cast mismatch, and SQL dialect difference. For HTTP failures, inspect the effective method, headers, route parameters, guard and middleware. For cached behavior, inspect the selected connection, namespace, configuration cache and compiled views before changing source.

Add a regression that fails for the demonstrated behavior, implement the smallest correction, then run the focused test. Broaden to the suite when the correction affects shared behavior. Do not encode the buggy output as the expected contract or loosen an assertion merely to pass.

## Core checkout verification

Application commands above run application tests. From a TinyCore source checkout use:

```bash
php tests/run.php --filter SoftDeleteTest
php tests/database.php --filter SoftDeleteTest
php tests/run.php
php tests/database.php
```

Inspect `tests/config.php` before service-dependent runs. The database matrix creates and cleans disposable MySQL/PostgreSQL databases and also runs SQLite; its administrative credentials need creation/deletion privileges. The full suite includes configured Redis and local HTTP/S3 fixtures. Missing dependencies and skipped cases must be reported separately from passing checks. Do not run tests against application data.

| Changed behavior | Useful assertions |
| --- | --- |
| Endpoint or middleware | Successful result, invalid input, unauthenticated/other-owner access, headers and early responses |
| Database/model | Returned value/type, stored state, unaffected rows, null/empty boundaries, rollback, query count and driver parity |
| Cache/locks | Expiry, missing versus null, namespace isolation, owner mismatch, contention and release after exceptions |
| Queue | Claim exclusivity, retry/failure, delayed availability, duplicate dispatch and visibility after commit |
| Session | Flash consumption, login regeneration, logout/invalidation, expiry and native HTTP handler persistence |
| Blade/resource | Escaping, omitted/null fields, fresh/cached output, buffer recovery and query-free serialization |
| Storage/client | Rejected paths, partial failures, cleanup and provider request/response contracts |

Use real service checks where emulation cannot establish the contract. An in-process application session test does not prove PHP cookie/session transport. A local S3 fixture does not certify a live cloud account. Test counts are behavioral evidence, not line/branch coverage or a production guarantee.
