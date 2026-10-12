# Spark-native feature map

Read this reference when a task touches a framework service beyond the ordinary route/request/model/resource path. It is a routing map, not a substitute for the installed source. Confirm exact signatures in `vendor/tinymvc/tinycore/src` and preserve the application's configured driver and provider setup.

## Choose the native boundary

| Need | Preferred entrypoint | Notes |
| --- | --- | --- |
| Container/app binding | `app()`, `get()`, `Spark\Facades\App` | Bind interfaces and scalar configuration in providers; use `singleton()` only for process-safe state. |
| Database transaction/connection | `Spark\Facades\DB`, `db()` | Use `DB::transaction(fn () => ...)` around related model writes. Use `DB::table()` only for framework/unmodeled or deliberately low-level work. |
| Model query/persistence | `Model::query()`, `Model::where()`, model scopes/relations | Hydrates models and preserves casts, timestamps, soft deletes, events, and relations. |
| Request input | `Spark\Http\Request`, `Spark\Http\Input` | Use `query()`, `post()`, `input()`, `only()`, `except()`, `file()`, `user()`, `auth()`. |
| Validation | `Spark\Foundation\Http\FormRequest`, `$request->validate()`, `Spark\Http\Validator` | FormRequest is the default for meaningful writes and authorization. |
| Authorization | `Spark\Facades\Gate`, `gate()`, `can()`, `authorize()`, auth middleware | Gate callbacks receive only explicit arguments; read the current user or pass it deliberately. |
| JSON representation | `Spark\Http\Resources\JsonResource` | Use resources for entities and lists; use `json()` for messages and intentional non-resource contracts. |
| Browser response | `view()`, `redirect()`, `back()`, `to_route()`, `response()->noContent()` | Keep redirects and validation errors compatible with the active frontend. |
| Cache | `cache($name)`, `Spark\Facades\Cache` | Cache is transient; include tenant/user/locale/permission dimensions in keys and invalidate after a successful write. |
| Shared lock | `lock($key)`, `Spark\Facades\Lock` | Use `withLock()` or explicit `lock()/unlock()` with bounded wait/lease and idempotent work. |
| Durable background work | `JobInterface` + `Dispatchable`, `job()`, `Spark\Queue\Queue` | Jobs are at-least-once; make handlers idempotent and choose dispatch/transaction boundaries deliberately. |
| Small post-response callback | `defer()` | Process-local only; it is not a queue and is lost if the process ends. |
| Independent bounded tasks | `Spark\Concurrency` | Parallelism depends on the runtime; inspect every result and do not capture PDO/request/model resources. |
| External executable | `Spark\Console\Process` | Use argument arrays, bounded timeouts, `successful()`, `output()`, `errorOutput()`, and `throw()`. |
| CLI interaction | `Spark\Console\Prompt` | Use `ask()`, `confirm()`, `choice()`, `table()`, and status helpers in commands, not request controllers. |
| Filesystem | `Spark\Utils\File`, `fm()` | Use for local paths and directories; do not pass untrusted user paths without normalization/authorization. |
| Configured file/S3 storage | `storage()`, `Spark\Facades\Storage` | Disk keys are relative; authorize private reads and use `temporaryUrl()` for signed S3 access. |
| Upload processing | `Storage::uploader()`, `uploader()` | Validate genuine uploaded files; preserve returned disk-relative keys and cleanup policy. |
| Images | `image()`, `Spark\Utils\Image` | Verify GD/driver availability and destination policy before resizing/compressing. |
| Outbound HTTP | `http()`, `Spark\Facades\Http` | Set timeout, headers/auth, content type, and failure handling; use the pool only for independent requests. |
| Mail | `mailer()`, `Spark\Facades\Mail` | Optional PHPMailer dependency; handle failures and avoid logging secrets. |
| Passwords/encryption | `Spark\Facades\Hash`, `Spark\Hash` | Use password APIs for passwords and encryption APIs for reversible values; never substitute a plain digest. |
| JWT primitives | `Spark\Utils\JWT` | Prefer configured `Auth` token lifecycle for user auth; raw encode/decode does not apply guard checks. |
| URLs | `route()`, `route_url()`, `Spark\Url` | Pass `Spark\Url` through resources; normalization converts it to a string. |
| Dates | `now()`, `carbon()`, `Spark\Carbon` | Model datetime casts already hydrate Carbon; resources should pass them through unchanged. |
| Collections/arrays/strings | `Spark\Support\Collection`, `Arr`, `Str` | Prefer the native helper when it clarifies intent; check the installed method before copying Laravel syntax. |
| Pagination | model query `paginate()`, `Spark\Utils\Paginator` | Resource collections retain paginator links/meta; do not hand-build the envelope. |
| Blade | `view()`, `Spark\Facades\Blade` | Use documented TinyCore directives/components; read [views and frontend](views-frontend.md). |
| Facades | `Spark\Facades\*` | Facades resolve registered services; inspect the facade's accessor and `@method` contract before using a method. |

## Cross-cutting rules

- Use dependency injection in reusable services and jobs when it makes the dependency visible; use a facade/helper when that is the established application style or the operation is a simple framework boundary.
- Keep request-specific identity, locale, tenant, and authorization in the current call. Do not store them in a singleton, static property, cache key omitted from a query, or serialized job accidentally.
- Do not use a service because a helper already expresses a one-line operation. Do use a service to coordinate multiple models, a transaction, cache/storage, external HTTP/mail, or retry/idempotency policy.
- Do not swallow framework exceptions or turn a failed process, validation, authorization, transaction, queue, storage, or HTTP call into a success response.
- When a feature has a framework reference, read it before writing. When the reference and installed source disagree, the installed source and current application code win.
