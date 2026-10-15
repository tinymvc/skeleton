---
name: tinymvc-development
description: Implement, debug, review, and test TinyMVC applications powered by TinyCore/Spark. Use for Spark-native PHP, routes, controllers, validation, ORM, JSON resources, Blade, FireLine, Inertia, storage, queues, cache, HTTP, auth, and other framework features, or for explicitly requested TinyCore changes.
---

# TinyMVC development

Write modern, readable Laravel-style PHP against the installed TinyCore implementation. The goal is familiar architecture and beautiful application code while preserving Spark's actual namespaces, return types, lifecycle, and frontend protocols. Laravel syntax is a style reference, never proof that an Illuminate API exists.

## Establish the context before coding

1. Work from the application root. Read `composer.json`, `composer.lock`, bootstrap routing/provider files, the nearest feature, and its tests before adding a new pattern.
2. Treat `vendor/tinymvc/tinycore/src` as the runtime source of truth for the installed version. Read the declaration and method body when a signature or behavior matters. Use [API discovery](references/api-discovery.md) for bounded lookups.
3. Check whether the task is application code, an optional frontend/integration, documentation, or an explicitly requested core change. During normal app work, vendor files are reference only; do not patch them or upgrade dependencies to make an example fit.
4. Load the primary reference for the feature, then only the related references it crosses. The references are a maintained decision guide, not a substitute for inspecting the target app.

## Spark-first architecture rules

These rules are defaults for new and edited code. Follow a nearby application convention when it is intentional and compatible, and explain a necessary deviation in the handoff.

### 1. Choose the response contract from the frontend

| Consumer | Preferred controller result |
| --- | --- |
| JSON API entity or entity list | `ThingResource::make($thing)` or `ThingResource::collection($items)` |
| JSON API paginator | `ThingResource::collection($query->paginate(20))`; the collection adds `data`, `links`, and `meta` |
| JSON API status, message, scalar, webhook, or deliberately non-resource document | `json([...], $status)` with the exact documented contract |
| Browser/Blade | `view(...)`, `redirect(...)`, `to_route(...)`, or `response()->noContent()` |
| FireLine visit/form | the installed FireLine `fire()`/`Fire::render()` response and its redirect/error helpers |
| Inertia visit/form | the installed Inertia `inertia()`/adapter response and redirect flow |

For API resources, define a `Spark\Http\Resources\JsonResource` per public entity and expose an explicit field list. Do not hand-wrap a model in `json(['data' => ...])`, return a model array from a resource controller, or manually build pagination metadata. `json()` remains appropriate for a message or a contract that is intentionally not a resource. Use `whenLoaded()` and `whenCounted()` so resources do not create N+1 queries. Pass model date and URL objects through unchanged; `JsonResource::normalize()` converts `Spark\Carbon` and `Spark\Url` recursively.

### 2. Validate and authorize at the request boundary

Use a dedicated `Spark\Foundation\Http\FormRequest` for create, update, delete/state-change, uploads, and any validation or authorization with meaningful policy. Type-hint it in the action; construction performs `authorize()` and `rules()` before the action runs. Read only validated values with `$request->validated()` (`Spark\Http\Input`), `$request->validated('field')`, or `$request->validated()->all()`.

Inline `$request->validate([...])` is suitable for small, read-only filters or a genuinely trivial one-field action. It must not become a shortcut for a major write workflow. Keep ownership and authorization explicit: route model binding establishes identity, while the request, policy, gate, middleware, or service must enforce who may act.

### 3. Use the ORM for domain entities

When an application model exists, start with `Post::query()`, `Post::where(...)`, `Post::findOrFail(...)`, `Post::create(...)`, or a model scope. Define and use Spark relations (`hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, `hasManyThrough`) with `with()`, `load()`, `whereHas()`, `whereDoesntHave()`, `whereRelation*()`, and relation aggregates before considering SQL joins.

Do not use `DB::table('posts')` or `query('posts')` as a model substitute: it bypasses hydration, casts, timestamps, soft-delete behavior, relations, scopes, and model lifecycle. Use `Spark\Facades\DB` for transactions, connections, locks, schema/ledger work, framework tables, or a deliberately low-level query where no model or relation expresses the requirement. Raw SQL must be parameterized and justified by the actual task.

Remember the installed return contracts: `create()` and `fill()` return a model; `save()` and `remove()` return `bool`; query `update()` and `delete()` return affected-row counts. A loaded model is deleted with `$model->remove()`. Check these results when failure matters instead of importing an Eloquent assumption.

### 4. Always dispatch jobs statically via `Dispatchable`

Application jobs implement `Spark\Queue\Contracts\JobInterface` and use `Spark\Queue\Dispatchable`. In controllers, models, and services, **always use the static dispatch methods**:

```php
// Dispatch to a queue
SendAccountMail::dispatch($user->id)->onQueue('emails');

// Dispatch with delay
SendAccountMail::dispatch($user->id)->onQueue('emails')->delay(60);

// Dispatch with deduplication (push once)
SyncReports::dispatchOnce()->onQueue('reports')->repeatEveryMinutes(5);

// Conditional dispatch
SendAccountMail::dispatchIf($user->is_active, $user->id);
SendAccountMail::dispatchUnless($user->is_banned, $user->id);

// Synchronous dispatch
ProcessPayment::dispatchSync($order->id);
```

**Never use `job(JobClass::class, [...])->dispatch(...)` or procedural `dispatch(...)`** in application domain logic. The `job()` helper is reserved strictly for recurring queue registration in `bootstrap/app.php` (`withQueue(jobs: [...])`) or raw callbacks like job([SomeClass::class, 'method'])->dispatch() or dispatch(...some_callable...).

### 5. Use native facades and fluent utilities

TinyCore provides first-class static facades under `Spark\Facades\*`. Write modern, expressive code:
- **Mail**: `Mail::to($user->email, $user->name)->subject('Welcome')->view('emails.welcome', ['user' => $user])->send();`
- **HTTP Client**: `$response = Http::withToken($token)->post('https://api.example.com/data', $payload);` check with `$response->successful()` and `$response->json('data.id')`.
- **Storage**: `Storage::disk('public')->put('path/file.txt', $data);` or `Storage::url($path)`.
- **Cache**: `Cache::remember('key', fn() => ..., '+1 hour');`, `Cache::store(...)`, `Cache::erase(...)`.
- **Auth & Gate**: `Auth::check()`, `Auth::user()`, `Auth::attempt(...)`; `Gate::define(...)`, `Gate::authorize(...)`.
- **Database**: `DB::transaction(fn() => ...);`.
- **Support Helpers**: `collect($items)`, `str($value)->slug()`, `Str::uuid()`, `Arr::get($data, 'key')`, `tap($model, fn($m) => ...)`, `throw_if($condition, DomainException::class, 'message')`.

### 6. Collection-first queries, pivot relations, and model events

- **Collection-First**: Default query execution should use `->get()` (which returns a `Spark\Support\Collection`) or `->paginate($limit)` rather than `->all()` (which returns a raw PHP array). Collections provide higher-order operations: `map()`, `filter()`, `pluck()`, `keyBy()`, `first()`.
- **Pivot Relations**: Manage `belongsToMany` pivots using native methods: `$model->tags()->attach([1, 2])`, `$model->tags()->detach([1])`, `$model->tags()->sync([2, 3])`, `$model->tags()->toggle([1])`.
- **Model Events**: Register model lifecycle reactions via `protected function events(): Events`:
  ```php
  protected function events(): Events
  {
      return Events::make(
          created: fn() => SendAccountMail::dispatch($this->id)->onQueue('emails'),
          updated: fn() => Cache::erase("user:{$this->id}"),
          deleted: fn() => Storage::delete($this->avatar_path),
      );
  }
  ```

### 7. Keep controllers thin and services purposeful

Controllers coordinate route/request/model/service/response work. Keep query composition close to models as scopes and relations. Put multi-step domain workflows, transactions, external effects, cache invalidation, file operations, or reusable business rules in focused `app/Services/*Service` classes. Do not create repositories, service layers, DTOs, or pipelines for a one-line operation; do create a service when it gives a stable responsibility and a testable boundary.

Prefer constructor or action injection through the Spark container. Do not capture a current request, user, tenant, PDO connection, or open transaction in a singleton or queued job. Use jobs for durable work, `defer()` only for small process-local post-response work, `Spark\Concurrency` for independent bounded tasks, and `Spark\Console\Process` for bounded subprocesses.

### 8. Use route model binding when the contract fits

Define `{post}` or `{post:slug}` and type-hint `Post $post` in the action. Spark resolves the model with `firstOrFail()` before invocation. Keep parameter names aligned. Use an explicit query/service when lookup needs ownership, compound conditions, trash visibility, or a non-model result; binding alone never proves authorization.

### 9. Follow the local style

- Four spaces, no tabs; class and method braces on the next line; braces for all control flow.
- One statement per line, descriptive names, explicit visibility and types where the real contract permits.
- Keep imports correct and minimal. The application base controller is normally `App\Http\Controllers\Controller`; request input is `Spark\Http\Input`.
- Put one call per line in long chains and one entry per line in long arrays/argument lists, with trailing commas.
- Prefer guard clauses, focused methods, direct expressions, and `fn (...) => ...` only for one expression. Avoid nested ternaries, hidden side effects, redundant wrappers, and comments that restate code.
- Use Spark's native validation, resources, relations, scopes, collections, `Arr`, `Str`, helpers, facades, cache/lock, queue, storage, HTTP, auth, Blade, and date utilities before inventing replacements.

## Frontend and feature routing

Inspect the installed packages and existing entrypoints before choosing a frontend. Do not install or mix Blade, FireLine, Inertia, React, Vue, or Orbit unless the task requires it. Read [frontend setup](references/frontend-setup.md) for protocol-specific setup and [views and frontend](references/views-frontend.md) for Blade syntax.

| Work | Read first | Spark-native direction |
| --- | --- | --- |
| routes/controllers/binding | [routing and controllers](references/routing-controllers.md) | `Spark\Facades\Route`, named routes, groups, resource routes, typed model binding |
| request/input/validation | [requests and validation](references/requests-validation.md) | `Request`, `Input`, `Validator`, `FormRequest`, file input |
| models/ORM/soft deletes | [models and relations](references/models-relations.md), [soft deletes](references/soft-deletes.md) | casts, scopes, relations, `with*`, `whereHas*`, `remove()` |
| SQL/transactions/locks | [queries](references/queries.md), [advanced workflows](references/advanced-workflows.md) | model queries first; `DB` for transactions and justified low-level work |
| API/web responses/resources | [responses](references/responses.md) | `JsonResource`, `ResourceCollection`, `json`, redirects, `noContent` |
| Blade/components/directives | [views and frontend](references/views-frontend.md) | documented TinyCore Blade directives and escaped output |
| FireLine/Inertia/Vite | [frontend setup](references/frontend-setup.md) | preserve each adapter's response protocol and asset entry |
| auth/gates/JWT | [auth and gate](references/auth-gate.md) | guards, `Auth`, `Gate`, `authorize`, token lifecycle |
| cache/locks | [cache and locks](references/cache-locks.md) | cache stores, `remember`, atomic locks, invalidation |
| jobs/queue/deferred work | [queues](references/queues.md), [concurrency](references/concurrency.md) | `JobInterface`, `Dispatchable`, retries, idempotency, process limits |
| files/uploads/S3/images | [files and storage](references/files-storage.md) | `Storage`, `Uploader`, `File`, `Image`, private/public URL policy |
| HTTP/mail/hash/url/date/utils | [native feature map](references/native-feature-map.md), [localization and dates](references/localization-dates.md) | inspect signatures; use helpers/facades and preserve boundary serialization |
| providers/container/events/pipelines | [container and pipelines](references/container-pipelines.md), [extensions and errors](references/extensions-errors.md) | bind at the right lifecycle, explicit event payloads, meaningful pipelines |
| migrations/config/bootstrap | [foundation](references/foundation.md), [migrations](references/migrations.md) | inspect generated schema and application bootstrap before changing it |
| tests/diagnosis/upgrades | [workflow and testing](references/workflow-testing.md), [upgrades](references/upgrades.md) | test observable contracts and the configured drivers |

## A complete feature workflow

Trace one existing route → action → request → model/service → response/view path before generating files. Then:

1. State the user-visible contract and frontend representation.
2. Inspect the installed declarations and nearby code for every uncertain method.
3. Add only the route, middleware, FormRequest, model scope/relation, resource/view, service/job, and migration pieces the feature needs.
4. Preserve ownership, tenant boundaries, casts, loaded relations, transactions, retry behavior, and external side-effect ordering.
5. Verify persisted state and response shape, not only a status code. Check the actual frontend protocol when an adapter is involved.

If TinyCore lacks a needed API, identify the gap and use a supported application-level design where possible. Do not silently invent a method, import Illuminate, copy an obsolete example, or patch vendor code. A dependency/core change stays within scope only when the user explicitly requests it.

## Verify and hand off

Run `php -l` on changed PHP files and the focused native tests (`php test --filter=Name` or `composer test`). Build frontend assets when frontend files change. For database, queue, cache, storage, driver, or HTTP lifecycle changes, test the affected configured backend and the failure/retry/ownership boundary that matters.

Before handing off, inspect the diff for:

- application jobs dispatched statically via `JobClass::dispatch()` and never `job(JobClass::class, ...)->dispatch(...)`;
- resource controllers using `JsonResource` for entities/collections and `json()` only for intentional non-resource contracts;
- FormRequests on substantial state-changing workflows;
- model-first queries with `->get()` (Collections) and native relations where a model exists;
- no `Carbon::parse($model->created_at)` or manual `toIsoUtcString()` inside resources;
- proper facade usage (`Mail`, `Http`, `Storage`, `Cache`, `DB`, `Auth`, `Gate`) rather than procedural helper sprawl;
- no accidental `$model->delete()`, wrong `App\Http\Controller`/`Spark\Http\Requests\Input` imports, invented Laravel APIs, or duplicated Spark behavior;
- tests and docs matching the installed TinyCore version and the target app's actual frontend.

Report the resulting behavior, tests actually run, and any concrete unverified limitation.
