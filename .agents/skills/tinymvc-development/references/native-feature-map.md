# Spark-native feature map

Read this reference when a task touches a framework service beyond the ordinary route/request/model/resource path. It is a routing map, not a substitute for the installed source. Confirm exact signatures in `vendor/tinymvc/tinycore/src` and preserve the application's configured driver and provider setup.

## Choose the native boundary

| Need | Preferred entrypoint | Notes |
| --- | --- | --- |
| Container/app binding | `app()`, `get()`, `Spark\Facades\App` | Bind interfaces and scalar configuration in providers; use `singleton()` only for process-safe state. |
| Database transaction/connection | `Spark\Facades\DB`, `db()` | Use `DB::transaction(fn () => ...)` around related model writes. Use `DB::table()` only for framework/unmodeled or deliberately low-level work. |
| Model query/persistence | `Model::query()`, `Model::where()`, model scopes/relations | Hydrates models and preserves casts, timestamps, soft deletes, events, and relations. |
| Request input | `Spark\Http\Request`, `Spark\Http\Input` | `Input` implements `Arrayable` and `ArrayAccess`. Pass validated input directly to `Model::create()`, `fill()`, `QueryBuilder::update()`, and `Auth::attempt()`. |
| Validation | `Spark\Foundation\Http\FormRequest`, `$request->validate()`, `Spark\Facades\Validator` | FormRequest is the default for meaningful writes and authorization. Returns an `Input` instance. |
| Authorization | `Spark\Facades\Gate`, `gate()`, `can()`, `authorize()`, auth middleware | Use `Gate::check()`, `Gate::allows()`, `Gate::denies()`, `Gate::authorize()`. Read `Auth::user()` or pass explicit arguments. |
| JSON representation | `Spark\Http\Resources\JsonResource` | Use resources for entities and lists; use `json()` for messages and intentional non-resource contracts. Auto-formats `Spark\Carbon` to ISO-8601 UTC. |
| Browser response | `view()`, `redirect()`, `back()`, `to_route()`, `response()->noContent()` | Keep redirects and validation errors compatible with the active frontend. |
| Cache | `Spark\Facades\Cache`, `cache()` | `Cache::remember()`, `Cache::get()`, `Cache::put()`, `Cache::forget()`, `Cache::make()`. Invalidate after a successful write. |
| Shared lock | `Spark\Facades\Lock`, `lock()` | Use `Lock::withLock()` or explicit `Lock::lock()` / `Lock::unlock()` with bounded wait/lease. |
| Durable background work | `JobInterface` + `Dispatchable` (`JobClass::dispatch()`) | Static dispatch: `JobClass::dispatch(...)->onQueue(...)`. Never use procedural `job()` in domain code. |
| Small post-response callback | `defer()` | Process-local only; it is not a queue and is lost if the process ends. |
| Independent bounded tasks | `Spark\Concurrency` | Parallelism depends on the runtime; inspect every result and do not capture PDO/request/model resources. |
| External executable | `Spark\Console\Process` | Use argument arrays, bounded timeouts, `successful()`, `output()`, `errorOutput()`, and `throw()`. |
| CLI interaction | `Spark\Console\Prompt` | Use `ask()`, `confirm()`, `choice()`, `table()`, and status helpers in commands, not request controllers. |
| Filesystem | `Spark\Utils\File`, `fm()` | Use for local paths and directories; do not pass untrusted user paths without normalization/authorization. |
| Configured file/S3 storage | `Spark\Facades\Storage`, `Storage::disk()` | Disk keys are relative; use `Storage::disk('public')`; `temporaryUrl()` for signed S3 access. |
| Upload processing | `Storage::uploader()`, `uploader()`, `$request->moveFile()` | Validate genuine uploaded files; preserve returned disk-relative keys and cleanup policy. |
| Images | `Spark\Utils\Image`, `image()` | Verify GD/driver availability and destination policy before resizing/compressing. |
| Outbound HTTP | `Spark\Facades\Http` | Fluent client: `Http::get()`, `Http::withToken()->post()`, check `$response->successful()`, `$response->json()`. |
| Mail | `Spark\Facades\Mail` | Fluent sending: `Mail::to()->subject()->view()->send()`. Send asynchronously via queued job in web requests. |
| Passwords/encryption | `Spark\Facades\Hash`, `Spark\Hash` | Use password APIs (`Hash::make()`, `Hash::check()`) for passwords; never substitute a plain digest. |
| JWT primitives | `Spark\Utils\JWT`, `Auth::createToken()` | Prefer configured `Auth` token lifecycle for user auth; raw encode/decode does not apply guard checks. |
| URLs | `route()`, `route_url()`, `Spark\Url` | Pass `Spark\Url` through resources; normalization converts it to a string. |
| Dates | `now()`, `carbon()`, `Spark\Carbon` | **Never use legacy PHP date functions** (`gmdate`, `date`, `time`, `strtotime`). App timezone is configured UTC. |
| Collections/arrays/strings | `Spark\Support\Collection`, `Arr`, `Str` | Prefer native fluent helpers (`collect()`, `str()`, `Arr::get()`); check installed methods. |
| Pagination | model query `paginate()`, `Spark\Utils\Paginator` | Resource collections retain paginator links/meta; do not hand-build the envelope. |
| Blade | `view()`, `Spark\Facades\Blade` | Use documented TinyCore directives/components (`Blade::directive()`, `Blade::share()`). |
| Facades | `Spark\Facades\*` | Static access: `App`, `Auth`, `Blade`, `Cache`, `DB`, `Event`, `Gate`, `Hash`, `Http`, `Lock`, `Log`, `Mail`, `Queue`, `Route`, `Schema`, `Session`, `Storage`, `Validator`. |

## Cross-cutting rules

- Use dependency injection in reusable services and jobs when it makes the dependency visible; use a facade/helper when that is the established application style or the operation is a simple framework boundary.
- Keep request-specific identity, locale, tenant, and authorization in the current call. Do not store them in a singleton, static property, cache key omitted from a query, or serialized job accidentally.
- Do not use a service because a helper already expresses a one-line operation. Do use a service to coordinate multiple models, a transaction, cache/storage, external HTTP/mail, or retry/idempotency policy.
- Do not swallow framework exceptions or turn a failed process, validation, authorization, transaction, queue, storage, or HTTP call into a success response.
- When a feature has a framework reference, read it before writing. When the reference and installed source disagree, the installed source and current application code win.
