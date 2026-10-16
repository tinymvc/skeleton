---
name: tinymvc-development
description: Implement, debug, review, and test TinyMVC applications powered by TinyCore/Spark. Use for Spark-native PHP, routes, controllers, validation, ORM, JSON resources, Blade, FireLine, Inertia, storage, queues, cache, HTTP, auth, and other framework features, or for explicitly requested TinyCore changes.
---

# TinyMVC development

Write modern, smart, clean, and beautiful Laravel-style PHP against the installed TinyCore implementation. The goal is familiar architecture and elegant application code while strictly utilizing Spark's native features, facades, contracts, return types, lifecycle, and frontend protocols. Laravel syntax is a style and elegance reference, never proof that an Illuminate API exists.

## Establish the context before coding

1. Work from the application root. Read `composer.json`, `composer.lock`, bootstrap routing/provider files, the nearest feature, and its tests before adding a new pattern.
2. Treat `vendor/tinymvc/tinycore/src` as the runtime source of truth for the installed version. Read the declaration and method body when a signature or behavior matters. Use [API discovery](references/api-discovery.md) for bounded lookups.
3. Check whether the task is application code, an optional frontend/integration, documentation, or an explicitly requested core change. During normal app work, vendor files are reference only; do not patch them or upgrade dependencies to make an example fit.
4. Load the primary reference for the feature, then only the related references it crosses. The references are a maintained decision guide, not a substitute for inspecting the target app.

## Spark-first architecture rules

These rules are defaults for new and edited code. Follow a nearby application convention when it is intentional and compatible, and explain a necessary deviation in the handoff.

### Smart vs. Non-Smart Syntax Standards

Always write smart, elegant, and native Spark syntax. Compare standard patterns below:

| Feature / Pattern | Non-Smart / Legacy Syntax ❌ | Smart & Elegant Spark Syntax ✅ |
| --- | --- | --- |
| **Current Timestamp** | `gmdate('Y-m-d H:i:s')` or `date(...)` | `now()->toDateTimeString()` |
| **Date Arithmetic** | `time() + 86400` or `strtotime('+7 days')` | `now()->addDays(7)` or `now()->addHours(2)` |
| **Date Parsing** | `new DateTime($val)` or `date('Y-m-d', strtotime($val))` | `carbon($val)` or `Carbon::parse($val)` |
| **Job Dispatching** | `job(SendMail::class, [$id])->dispatch('emails')` | `SendMail::dispatch($id)->onQueue('emails')` |
| **Request Validation** | Controller inline validation with manual array conversions | Dedicated `FormRequest` class injected into action |
| **Arrayable Input** | `Model::create($request->validated()->all())` | `Model::create($request->validated())` |
| **Input Shaping** | `array_merge($request->validated()->all(), ['user_id' => $id])` | `$request->validated()->merge(['user_id' => Auth::id()])` |
| **Model Creation** | `$post = new Post(); $post->title = ...; $post->save();` | `Post::create($request->validated())` |
| **Model Deletion** | `$model->delete();` *(QueryBuilder only!)* | `$model->remove();` *(Instance method)* or `Post::destroy($id)` |
| **Database Transactions** | `DB::getDB()->beginTransaction(); ... DB::commit();` | `DB::transaction(fn () => ...);` |
| **Distributed Locks** | Sleep loops or procedural lock checks | `Lock::withLock('order:1', fn () => ..., timeout: 10)` |
| **Cache Storing** | `if (!Cache::has($k)) { Cache::put($k, ...); }` | `Cache::remember('key', fn () => ..., '+1 hour')` |
| **Collections** | `$users->map(fn ($u) => $u->title)` | `$users->map->title` *(Higher-Order Proxy)* |
| **Collection Filtering**| `$users->filter(fn ($u) => $u->is_active)` | `$users->filter->is_active` *(Higher-Order Proxy)* |
| **JSON Resources** | `json(['data' => $post])` or manual `Carbon::parse()` | `PostResource::make($post)` *(Dates auto-converted to ISO UTC)* |
| **Outbound HTTP** | `curl_init()` or `file_get_contents()` | `Http::withToken($token)->post($url, $payload)` |
| **Environment Check** | `getenv('APP_ENV') === 'testing'` | `App::isTesting()` or `App::isDebugMode()` |

### 1. Choose the response contract from the frontend

| Consumer | Preferred controller result |
| --- | --- |
| JSON API entity or entity list | `ThingResource::make($thing)` or `ThingResource::collection($items)` |
| JSON API paginator | `ThingResource::collection($query->paginate(20))`; the collection adds `data`, `links`, and `meta` |
| JSON API status, message, scalar, webhook, or deliberately non-resource document | `json([...], $status)` with the exact documented contract |
| Browser/Blade | `view(...)`, `redirect(...)`, `to_route(...)`, or `response()->noContent()` |
| FireLine visit/form | the installed FireLine `fire()`/`Fire::render()` response and its redirect/error helpers |
| Inertia visit/form | the installed Inertia `inertia()`/adapter response and redirect flow |

For API resources, define a `Spark\Http\Resources\JsonResource` per public entity and expose an explicit field list. Do not hand-wrap a model in `json(['data' => ...])`, return a model array from a resource controller, or manually build pagination metadata. `json()` remains appropriate for a message or a contract that is intentionally not a resource. Use `whenLoaded()` and `whenCounted()` so resources do not create N+1 queries. Pass model date and URL objects through unchanged; `JsonResource::normalize()` converts `Spark\Carbon` and `Spark\Url` recursively into ISO-8601 UTC strings and URL strings.

### 2. Validate and authorize at the request boundary with FormRequest & Input

Use a dedicated `Spark\Foundation\Http\FormRequest` for create, update, delete/state-change, uploads, and any validation or authorization with meaningful policy. Type-hint it in the action; construction performs `authorize()` and `rules()` before the action runs.

#### The `Spark\Http\Input` Class: Smart, Fluent, and Arrayable
`$request->validated()` returns a `Spark\Http\Input` instance. It implements:
- `Spark\Contracts\Support\Arrayable`
- `\ArrayAccess` (`$validated['field']`)
- Magic property access (`$validated->field`)
- `\IteratorAggregate`, `Spark\Contracts\Support\Jsonable`, and `\Stringable`

Because `Input` implements `Arrayable`, **pass it directly** into any TinyCore API accepting `array|Arrayable`:
- `Model::create($request->validated())`
- `Model::createOrUpdate($request->validated(), uniqueBy: ['slug'])`
- `Model::firstOrCreate($request->validated()->only('slug'), $request->validated()->except('slug'))`
- `$model->fill($request->validated())->save()`
- `QueryBuilder::update($request->validated())`
- `DB::table('logs')->insert($request->validated())`
- `Auth::attempt($request->validated()->only('email', 'password'), $request->boolean('remember'))`

```php
// BAD / UN-SMART - Redundant ->all() or ->toArray() conversions:
$post = Post::create($request->validated()->all());
Auth::attempt($request->validated()->only(['email', 'password'])->all());

// GOOD / SMART & ELEGANT - Direct Arrayable passing:
$post = Post::create($request->validated());

// Fluent chaining with only(), except(), merge():
$post = Post::create(
    $request->validated()
        ->merge(['user_id' => Auth::id()])
        ->except('tags')
);

// In-place model update:
$post->fill($request->validated())->save();
```

Inline `$request->validate([...])` is suitable only for small, read-only filters or a genuinely trivial one-field action.

### 3. Never use legacy PHP date functions; use native `Spark\Carbon` and `now()`

> [!CAUTION]
> **Prohibition of Procedural Date Functions**:
> **NEVER** use procedural PHP date functions in application code:
> - ❌ `gmdate('Y-m-d H:i:s')`
> - ❌ `date('Y-m-d')`
> - ❌ `time()` or `time() + 86400`
> - ❌ `strtotime('+1 day')`
> - ❌ `microtime()`
>
> TinyCore application boot automatically configures PHP's default timezone to `config('app.timezone', 'UTC')` via `date_default_timezone_set()`. The native **`Spark\Carbon`** class and global **`now()`** helper are fully timezone-aware and set to application UTC.

Always write expressive Carbon calls:
- **Current time**: `now()` or `Carbon::now()` (returns `Spark\Carbon` in app UTC).
- **Date presets**: `Carbon::today()`, `Carbon::tomorrow()`, `Carbon::yesterday()`.
- **Parsing**: `carbon('2026-10-16 12:00:00')` or `Carbon::parse($value)`.
- **Formatting**: `now()->toDateTimeString()`, `now()->toDateString()`, `now()->toTimeString()`, `now()->toIsoUtcString()`, `now()->format('M j, Y')`.
- **Timestamps**: `now()->timestamp`, `now()->addDays(7)->timestamp`.
- **Relative math**: `now()->addDays(30)`, `now()->subHours(2)`, `now()->startOfDay()`, `now()->diffForHumans()`.
- **Model casts**: Cast timestamps and dates with `'datetime'` or `'date'`; Spark ORM hydrates them automatically as `Spark\Carbon`.
- **Resources**: Pass date attributes through directly; `JsonResource::normalize()` automatically converts `Spark\Carbon` to ISO-8601 UTC strings (`$value->toIsoUtcString()`). Never call `Carbon::parse()` or format manually inside resources.
- **Queries**: Pass Carbon directly to query conditions: `Post::where('published_at', '<=', now())`, `whereDate('created_at', today())`.

### 4. Always dispatch jobs statically via `Dispatchable`

Application jobs implement `Spark\Queue\Contracts\JobInterface` and use `Spark\Queue\Dispatchable`. In controllers, models, and services, **always use the static dispatch methods**:

```php
// Dispatch to a queue
SendAccountEmail::dispatch($user->id)->onQueue('emails');

// Dispatch with delay (in seconds)
SendAccountEmail::dispatch($user->id)->onQueue('emails')->delay(60);

// Dispatch with deduplication (push once)
SyncReports::dispatchOnce()->onQueue('reports')->repeatEveryMinutes(5);

// Conditional dispatch
SendAccountEmail::dispatchIf($user->is_active, $user->id);
SendAccountEmail::dispatchUnless($user->is_banned, $user->id);

// Synchronous dispatch in current process
ProcessPayment::dispatchSync($order->id);
```

**Never use `job(JobClass::class, [...])->dispatch(...)` or procedural `dispatch(...)`** in application domain logic. The `job()` helper is reserved strictly for recurring queue registration in `bootstrap/app.php` (`withQueue(jobs: [...])`) or raw closure callbacks.

### 5. Use native static facades (`Spark\Facades\*`)

TinyCore provides first-class static facades under `Spark\Facades\*`. Always prefer facades over procedural global helpers for core services:
- **Database (`DB`)**: `DB::transaction(fn() => ...)`, `DB::table('users')`, `DB::raw(...)`.
- **Auth (`Auth`)**: `Auth::check()`, `Auth::user()`, `Auth::id()`, `Auth::attempt(...)`, `Auth::loginUsingId($id)`.
- **Gate (`Gate`)**: `Gate::define(...)`, `Gate::check(...)`, `Gate::allows(...)`, `Gate::denies(...)`, `Gate::authorize(...)`.
- **Cache (`Cache`)**: `Cache::remember('key', fn() => ..., '+1 hour')`, `Cache::get(...)`, `Cache::put(...)`, `Cache::forget(...)`, `Cache::make('store')`.
- **Lock (`Lock`)**: `Lock::withLock('order:1', fn() => ..., timeout: 10, waitTimeout: 5)`, `Lock::lock(...)`, `Lock::unlock(...)`.
- **Mail (`Mail`)**: `Mail::to($user->email, $user->name)->subject('Welcome')->view('emails.welcome', ['user' => $user])->send();`.
- **HTTP Client (`Http`)**: `$response = Http::withToken($token)->post('https://api.example.com/data', $payload);` check with `$response->successful()` and `$response->json('data.id')`.
- **Storage (`Storage`)**: `Storage::disk('public')->put('path/file.txt', $data);` or `Storage::disk('public')->url($path)`.
- **Session (`Session`)**: `Session::get('key')`, `Session::put('key', $val)`, `Session::flash('status', $msg)`.
- **Schema (`Schema`)**: `Schema::create('posts', function (Blueprint $table) { ... })`, `Schema::table(...)`.
- **Validator (`Validator`)**: `Validator::make($rules, $data)`.
- **Blade (`Blade`)**: `Blade::directive('name', fn() => ...)`, `Blade::share('key', $val)`.
- **Log (`Log`)**: `Log::info('Message', ['context' => 1])`, `Log::error(...)`.
- **Hash (`Hash`)**: `Hash::make('password')`, `Hash::check('password', $hash)`.
- **Route (`Route`)**: `Route::get(...)`, `Route::post(...)`, `Route::resource(...)`, `Route::group(...)`.

### 6. Use the ORM for domain entities

When an application model exists, start with `Post::query()`, `Post::where(...)`, `Post::findOrFail(...)`, `Post::create(...)`, or a model scope. Define and use Spark relations (`hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, `hasManyThrough`) with `with()`, `load()`, `whereHas()`, `whereDoesntHave()`, `whereRelation()`, and relation aggregates before considering SQL joins.

Do not use `DB::table('posts')` or `query('posts')` as a model substitute: it bypasses hydration, casts, timestamps, soft-delete behavior, relations, scopes, and model lifecycle. Use `Spark\Facades\DB` for transactions, connections, locks, schema/ledger work, framework tables, or a deliberately low-level query where no model or relation expresses the requirement.

Remember the installed return contracts:
- `create()` and `fill()` return a model instance.
- `save()` and `remove()` return `bool`. A loaded model instance is deleted with **`$model->remove()`** (never `$model->delete()`).
- Query `update()` and `delete()` return affected-row counts.

### 7. Collection-first queries, pivot relations, and model events

- **Collection-First**: Default query execution should use `->get()` (which returns a `Spark\Support\Collection`) or `->paginate($limit)` (which returns a `Spark\Utils\Paginator`) rather than `->all()` (which returns a raw PHP array). Collections provide higher-order operations: `map()`, `filter()`, `pluck()`, `keyBy()`, `first()`.
- **Pivot Relations**: Manage `belongsToMany` pivots using native methods: `$model->tags()->attach([1, 2])`, `$model->tags()->detach([1])`, `$model->tags()->sync([2, 3])`, `$model->tags()->toggle([1])`.
- **Model Events**: Register model lifecycle reactions via `protected function events(): Events`:
  ```php
  protected function events(): Events
  {
      return Events::make(
          created: fn() => SendAccountEmail::dispatch($this->id)->onQueue('emails'),
          updated: fn() => Cache::forget("user:{$this->id}"),
          deleted: fn() => Storage::disk('public')->delete($this->avatar_path),
      );
  }
  ```

### 8. Fluent migrations with foreign key constraints

Use Spark's expressive foreign key constraint methods on blueprints:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->setNullOnDelete();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content');
    $table->boolean('published')->default(false);
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

Constraint methods on `foreignId`: `cascadeOnDelete()`, `cascadeOnUpdate()`, `setNullOnDelete()`, `setNullOnUpdate()`, `restrictOnDelete()`, `restrictOnUpdate()`, `noActionOnDelete()`, `noActionOnUpdate()`.

### 9. Keep controllers thin and services purposeful

Controllers coordinate route/request/model/service/response work. Keep query composition close to models as scopes and relations. Put multi-step domain workflows, transactions, external effects, cache invalidation, file operations, or reusable business rules in focused `app/Services/*Service` classes. Do not create repositories, service layers, DTOs, or pipelines for a one-line operation; do create a service when it gives a stable responsibility and a testable boundary.

Prefer constructor or action injection through the Spark container. Do not capture a current request, user, tenant, PDO connection, or open transaction in a singleton or queued job. Use jobs for durable work, `defer()` only for small process-local post-response work, `Spark\Concurrency` for independent bounded tasks, and `Spark\Console\Process` for bounded subprocesses.

### 10. Use route model binding when the contract fits

Define `{post}` or `{post:slug}` and type-hint `Post $post` in the action. Spark resolves the model with `firstOrFail()` before invocation. Keep parameter names aligned. Use an explicit query/service when lookup needs ownership, compound conditions, trash visibility, or a non-model result; binding alone never proves authorization.

### 11. Follow the local style

- Four spaces, no tabs; class and method braces on the next line; braces for all control flow.
- One statement per line, descriptive names, explicit visibility and types where the real contract permits.
- Keep imports correct and minimal. The application base controller is `App\Http\Controllers\Controller`; request input is `Spark\Http\Input`.
- Put one call per line in long chains and one entry per line in long arrays/argument lists, with trailing commas.
- Prefer guard clauses, focused methods, direct expressions, and `fn (...) => ...` only for one expression. Avoid nested ternaries, hidden side effects, redundant wrappers, and comments that restate code.
- Use Spark's native validation, resources, relations, scopes, collections, `Arr`, `Str`, helpers, facades, cache/lock, queue, storage, HTTP, auth, Blade, and date utilities before inventing replacements.

## Frontend and feature routing

Inspect the installed packages and existing entrypoints before choosing a frontend. Do not install or mix Blade, FireLine, Inertia, React, Vue, or Orbit unless the task requires it. Read [frontend setup](references/frontend-setup.md) for protocol-specific setup and [views and frontend](references/views-frontend.md) for Blade syntax.

| Work | Read first | Spark-native direction |
| --- | --- | --- |
| routes/controllers/binding | [routing and controllers](references/routing-controllers.md) | `Spark\Facades\Route`, named routes, groups, resource routes, typed model binding |
| request/input/validation | [requests and validation](references/requests-validation.md) | `Request`, `Input` (`Arrayable`/`ArrayAccess`), `Validator`, `FormRequest`, file input |
| dates/time/Carbon | [localization and dates](references/localization-dates.md) | `now()`, `carbon()`, `Spark\Carbon`; never `gmdate()`/`date()`/`time()`; auto ISO serialization in resources |
| models/ORM/soft deletes | [models and relations](references/models-relations.md), [soft deletes](references/soft-deletes.md) | casts, scopes, relations, `with*`, `whereHas*`, `remove()`, events |
| SQL/transactions/locks | [queries](references/queries.md), [advanced workflows](references/advanced-workflows.md) | model queries first; `DB` for transactions; `Lock::withLock()` for locks |
| API/web responses/resources | [responses](references/responses.md) | `JsonResource`, `ResourceCollection`, `json`, redirects, `noContent` |
| Blade/components/directives | [views and frontend](references/views-frontend.md) | `Spark\Facades\Blade` directives, components, and escaped output |
| FireLine/Inertia/Vite | [frontend setup](references/frontend-setup.md) | preserve each adapter's response protocol and asset entry |
| auth/gates/JWT | [auth and gate](references/auth-gate.md) | guards, `Auth`, `Gate`, `authorize`, `Gate::check()`, token lifecycle |
| cache/locks | [cache and locks](references/cache-locks.md) | `Spark\Facades\Cache`, `Spark\Facades\Lock`, `remember`, `withLock`, invalidation |
| jobs/queue/deferred work | [queues](references/queues.md), [concurrency](references/concurrency.md) | `JobInterface`, `Dispatchable` (`JobClass::dispatch()`), retries, idempotency |
| files/uploads/S3/images | [files and storage](references/files-storage.md) | `Storage`, `Uploader`, `File`, `Image`, private/public URL policy |
| HTTP/mail/hash/url/utils | [native feature map](references/native-feature-map.md), [mail and http](references/mail-http.md) | `Spark\Facades\Http`, `Spark\Facades\Mail`, `Spark\Facades\Hash`, `Spark\Url` |
| providers/container/events/pipelines | [container and pipelines](references/container-pipelines.md), [extensions and errors](references/extensions-errors.md) | bind at the right lifecycle, explicit event payloads, meaningful pipelines |
| migrations/config/bootstrap | [foundation](references/foundation.md), [migrations](references/migrations.md) | `Spark\Facades\Schema`, blueprint foreign keys (`cascadeOnDelete`), application bootstrap |
| tests/diagnosis/upgrades | [workflow and testing](references/workflow-testing.md), [upgrades](references/upgrades.md) | test observable contracts and configured drivers |

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

- **Dates**: strictly `now()`, `carbon()`, `Spark\Carbon`, and Carbon formatting methods; **never** `gmdate()`, `date()`, `time()`, `strtotime()`, or `microtime()`;
- **Input as Arrayable**: passed directly to `Model::create($request->validated())`, `fill()`, `update()`, or `Auth::attempt()` without unnecessary `->all()` or `->toArray()` wrappers;
- **Background Jobs**: dispatched statically via `JobClass::dispatch()` (with `onQueue()`, `delay()`, `dispatchOnce()`, `dispatchIf()`, etc.) and never procedural `job(JobClass::class, ...)->dispatch(...)`;
- **Facades**: first-class `Spark\Facades\*` usage (`DB`, `Auth`, `Gate`, `Cache`, `Lock`, `Mail`, `Http`, `Storage`, `Route`, `Log`, `Blade`, `Queue`, `Schema`, `Session`, `Validator`) rather than procedural helper sprawl;
- **Model Deletion**: using `$model->remove()` on model instances and never `$model->delete()`;
- **Resource Controllers**: using `JsonResource` for entities/collections and `json()` only for intentional non-resource contracts;
- **Automatic Date Serialization in Resources**: no `Carbon::parse($model->created_at)` or manual `toIsoUtcString()` inside resources;
- **FormRequests**: on substantial state-changing workflows;
- **Model-First Queries**: with `->get()` (Collections) and native relations where a model exists;
- **Clean Formatting**: four-space indentation, class/method braces on next line, trailing commas on multiline arrays/arguments, no unnecessary wrappers or comments restating code;
- **No Invalid Imports**: no accidental `Illuminate\*`, wrong `App\Http\Controller`/`Spark\Http\Requests\Input` imports, invented Laravel APIs, or duplicated Spark behavior;
- **Tests and Docs**: matching the installed TinyCore version and the target app's actual frontend.

Report the resulting behavior, tests actually run, and any concrete unverified limitation.
