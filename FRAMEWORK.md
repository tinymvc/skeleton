# TinyCore Framework Context for AI Agents

This document is a working context file for Claude, Codex, or any other AI agent that needs to modify this repository. It summarizes the architecture, conventions, module responsibilities, and important framework-specific behavior of TinyCore, the core package for the TinyMVC framework.

Use this file before making changes. TinyCore is intentionally small, Laravel-like in ergonomics, and custom in implementation. Do not assume full Laravel internals exist.

## Project Identity

- Package: `tinymvc/tinycore`
- Namespace: `Spark\\`
- PHP requirement: `>=8.2`
- Composer autoload:
  - PSR-4: `Spark\\` -> `src/`
  - Autoloaded helper files:
    - `src/Foundation/helpers.php`
    - `src/Support/functions.php`
    - `src/Support/helpers.php`
- External hard dependency:
  - `doctrine/inflector`
- Optional suggested extensions/libraries:
  - `ext-pdo_sqlite` for default sqlite cache, lock, and queue drivers
  - `ext-redis` for redis cache, lock, and queue drivers
  - `ext-curl` for HTTP client
  - `ext-fileinfo`, `ext-gd`
  - `phpmailer/phpmailer`
  - `league/commonmark`, `voku/portable-ascii`, `ramsey/uuid`

## Important Repository Rule

`src/Support` is Laravel-derived support code. Treat it like vendor-style compatibility code.

Avoid editing `src/Support` unless the user explicitly asks for that folder. Prefer changing TinyCore-owned modules in `src/Foundation`, `src/Http`, `src/Routing`, `src/Database`, `src/Utils`, `src/Queue`, `src/View`, `src/Console`, `src/Facades`, and `src/Contracts`.

## High-Level Structure

- `src/Foundation`: application bootstrap, service providers, console stubs, framework helpers, built-in HTTP middleware, exception types, built-in error/tracer views.
- `src/Http`: request, response, session, auth, gate, validation/input, middleware pipeline, HTTP client.
- `src/Routing`: router, route objects, route groups, resource routes, routing contracts/exceptions.
- `src/Database`: PDO wrapper, query builder, active-record-like model, schema builder, migrations, casts, relations.
- `src/Utils`: cache, lock, redis connector, mail, image, upload, paginator, carbon-like date class, Vite, tracer.
- `src/Queue`: sqlite/redis queue, job abstraction, queue contracts/exceptions.
- `src/View`: Blade-like renderer/compiler and attributes.
- `src/Console`: command registry, console runner, prompt/process helpers.
- `src/Facades`: static facades into the application container.
- `src/Contracts`: framework interfaces.
- `src/Exceptions`: shared exception types.

## Application Bootstrap and Lifecycle

Main class: `Spark\Foundation\Application`

The application extends `Spark\Container` and is stored as `Application::$app`.

Constructor responsibilities:

1. Set `Application::$app`.
2. Start `Tracer`.
3. Load env with `DotEnv::bootstrap($this->path)`.
4. Register core singleton services:
   - `Translator`
   - `DB`
   - `Hash`
   - `Blade`
   - `Queue`
   - `Router`
   - `Middleware`
   - `Events`
5. In web mode (`is_web()`), also register:
   - `Session`
   - `InputErrors`
   - `Request`
   - `Response`
   - `Gate`
   - `Auth`
   - `Vite`

Common bootstrap API:

```php
Application::create($rootPath, config: 'config', providers: [...])
    ->withApp(...)
    ->withMiddleware(...)
    ->withRouting(...)
    ->withQueue(...)
    ->run();
```

`withApp()` accepts:

- `config` as array: merged directly into app config.
- `config` as string: discovers PHP config files under the folder and caches merged config under `bootstrap/cache/{sanitized_folder}.php`.
- `providers`: service provider classes or objects with `register()`.
- `middlewares`: map of aliases to middleware class/callable.

`withRouting()`:

- Loads API routes inside a group:
  - prefix: `api`
  - middleware: `cors`
  - without middleware: `csrf`
- Loads webhook routes inside a group:
  - prefix: `webhook`
  - without middleware: `csrf`
- Loads web and command route files directly.

`run()` lifecycle:

1. Dispatch `app:booting` in debug mode.
2. Set timezone from `config('app.timezone', 'UTC')`.
3. Boot service providers.
4. Dispatch `app:booted` in debug mode.
5. Resolve `Router` and `Request`.
6. Dispatch request through router.
7. Send returned `Response`.
8. Dispatch `app:terminated` in debug mode.
9. Map framework exceptions to `abort()` responses:
   - route/item/not found -> 404
   - authorization -> 403
   - invalid CSRF -> 419
   - too many requests -> 429
10. Custom exception handlers registered through `withExceptions()` may return a `Response`.
11. Unhandled exceptions are passed to `Tracer`.

## Service Container

Main class: `Spark\Container`

Important methods:

- `bind($abstract, $concrete = null)`
- `singleton($abstract, $concrete = null)`
- `instance($abstract, $instance)`
- `alias($alias, $abstract)`
- `get($abstract)`
- `make($abstract, $parameters = [])`
- `call($callableOrClassMethod, $parameters = [])`
- `when($concrete, $needs, $give)` for contextual binding
- `addServiceProvider($provider)`
- `bootServiceProviders()`
- `forget()`, `reset()`, `flush()`

`Application` inherits this container. Prefer resolving dependencies through `app()`, `get()`, constructor injection, or `Application::$app->make()`.

## Environment and Config

Main class: `Spark\DotEnv`

Env cache:

- `DotEnv::bootstrap($basePath)` loads `.env` and caches to `bootstrap/cache/env.php`.
- `.env` parsing supports comments, quotes, booleans, null, empty, integers, floats, `export KEY=VALUE`, inline comments, and `${VAR}` interpolation.
- `DotEnv::isFresh()` compares env cache mtime with `.env` mtime.

Config discovery:

- `DotEnv::discoverConfig($folder, $cache, $env)` scans PHP config files recursively and writes a compiled payload:
  - `config`: merged config array
  - `files`: config file mtimes
  - `env`: `.env` path and mtime signature
  - `generated_at`
- If a config PHP file changes, is added, removed, or `.env` changes, config cache is stale and rebuilt.
- Config keys are derived from relative config file paths:
  - `config/app.php` -> `app`
  - `config/cache.php` -> `cache`
  - nested files become dot-like keys.

Use:

```php
config('app.debug');
config(['app.debug' => true]);
env('APP_KEY', 'fallback');
```

Recommended config shapes:

```php
// config/database.php
return [
    'driver' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'file' => dirname(__DIR__) . '/database/sqlite.db',
        ],
        'default' => [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'name' => env('DB_DATABASE', 'spark'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],
];
```

```php
// config/cache.php
return [
    'driver' => env('CACHE_DRIVER', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'path' => dirname(__DIR__) . '/storage/cache',
        ],
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_DATABASE', 0),
            'prefix' => env('REDIS_PREFIX', 'spark'),
            'timeout' => env('REDIS_TIMEOUT', 0.0),
            'read_timeout' => env('REDIS_READ_TIMEOUT', 0.0),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
    ],
];
```

```php
// config/queue.php
return [
    'driver' => env('QUEUE_DRIVER', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'path' => dirname(__DIR__) . '/storage/queue/jobs.db',
        ],
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_DATABASE', 0),
            'prefix' => env('REDIS_PREFIX', 'spark'),
            'timeout' => env('REDIS_TIMEOUT', 0.0),
            'read_timeout' => env('REDIS_READ_TIMEOUT', 0.0),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
    ],
];
```

Do not reintroduce old path helpers such as `cache_dir()` for cache/lock/queue storage. Use config.

## HTTP Request

Main class: `Spark\Http\Request`

Constructor parses from PHP globals:

- method from `$_SERVER['REQUEST_METHOD']`
- path from `REQUEST_URI`
- root URL and full URL from host/protocol server values
- headers from `HTTP_*`
- server params
- files
- query params
- post params plus JSON body for `POST`, `PUT`, `PATCH`, `DELETE` when `$_POST` is empty

Method override:

- Only for original `POST`
- Reads `HTTP_X_HTTP_METHOD_OVERRIDE` or `_method`
- Allows `PUT`, `PATCH`, `DELETE`

Useful request API:

- `getMethod()`, `isGet()`, `isPost()`, `isPut()`, `isDelete()`, `isMethod()`
- `isPostBack()` means `POST`, `PUT`, `PATCH`, or `DELETE`
- `getPath()`, `getUrl()`, `getUri()`, `getRootUrl()`
- `query()`, `post()`, `file()`, `server()`, `header()`
- `all()`, `only()`, `except()`, `input()`, `safe()`
- `route()`, `mergeRouteParams()`, `getRouteParams()`
- `expectsJson()`, `isAjax()`, `isFirelineRequest()`
- validation: `validate()`, `validated()`, `errors()`
- auth/session helpers: `auth()`, `user()`, `isAuthenticated()`, `session()`

`parseRootUrl()` validates host using a conservative host regex to prevent header injection.

## HTTP Response

Main class: `Spark\Http\Response`

Important API:

- `new Response($content = '', $statusCode = 200, $headers = [])`
- `setContent()`, `write()`
- `setStatusCode()`
- `setHeader()`, `withHeaders()`
- `json()`, `withJson()`
- `redirect()`, `routeRedirect()`, `back()`
- `with()`, `withErrors()`, `withInput()`
- `noCache()`, `cache()`, `noContent()`
- `file()`, `download()`
- `send()`
- `sendAndContinue()`

`send()`:

- Handles redirects by sending `Location` and exiting.
- Converts arrays and `Arrayable` objects to JSON.
- Converts non-string content to string.
- Calls `http_response_code()`.
- Sends all headers.
- Echoes content.

When returning arrays or `Arrayable` from a route, `Router::parseHttpResponse()` creates a `Response`, and `Response::send()` will JSON encode.

## Routing

Main classes:

- `Spark\Routing\Router`
- `Spark\Routing\Route`
- `Spark\Routing\RouteGroup`
- `Spark\Routing\RouteResource`

Route definitions are builder objects. Example:

```php
route()->get('/users/{id}', [UserController::class, 'show'])->name('users.show');
route()->post('/users', [UserController::class, 'store'])->middleware('auth');
route()->group(['prefix' => 'api', 'middleware' => ['cors']], function () {
    route()->get('/health', fn() => ['ok' => true]);
});
```

Router supports:

- HTTP verbs: `get`, `post`, `put`, `patch`, `delete`, `options`
- `any`
- `match`
- `view`
- `fireline`
- `redirect`
- `resource`
- `group`
- `fallback`
- route `name`, `prefix`, `path`, `middleware`, `withoutMiddleware`

Dispatch:

1. Iterate registered routes.
2. `matchRoute()` checks HTTP method and path.
3. On match, route params are merged into `Request`.
4. Resolve `Middleware`.
5. Convert template routes to view callbacks.
6. Execute route through middleware pipeline.
7. Destination calls route callback through container `Application::$app->call()`.
8. Parse result to `Response`.

Route path matching:

- `HEAD` requests are treated as `GET`.
- Required params: `/users/{id}`
- Optional params: `/users/{id?}`
- Wildcard segment: `*`
- Matching allows optional trailing slash.
- Route params are mapped by declaration order.

CORS preflight routing:

- A request is CORS preflight when method is `OPTIONS` and it has both `Origin` and `Access-Control-Request-Method`.
- If a route explicitly allows `OPTIONS`, it handles the request normally.
- If not, router compares the route method against `Access-Control-Request-Method` so route-level CORS middleware can respond.
- Defensive behavior: if no middleware stops synthetic preflight, router returns empty `204` instead of executing a non-OPTIONS controller.

## Middleware Pipeline

Main class: `Spark\Http\Middleware`

Middleware registration:

```php
app()->withMiddleware(
    register: [
        'auth' => App\Http\Middlewares\AuthMiddleware::class,
        'cors' => App\Http\Middlewares\CorsMiddleware::class,
    ],
    queue: ['csrf']
);
```

Core behavior:

- `register($alias, $middleware)`
- `registerMany($map)`
- `queue($middleware)` for global/default stack
- `process($request, $queue = [], $except = [], $destination = null)`
- Middleware names can include parameters: `throttle:60,1,api`
- Route middleware is combined with global stack, then `withoutMiddleware` filters by alias base name.
- Pipeline is built from the end backwards.
- Standard middleware signature:

```php
public function handle(Request $request, Closure $next, ...$parameters): mixed
{
    return $next($request);
}
```

Middleware may:

- Return a `Response` or response-like value early.
- Throw a framework exception.
- Call `$next($request)` and mutate the returned `Response`.

## Built-In Middleware

### CORS

Base class: `Spark\Foundation\Http\Middlewares\CorsAccessControl`

It is abstract. Applications should extend it and set protected `$config`.

Config keys:

- `origin`: `'*'`, string, comma-separated string, or array. Supports wildcard patterns such as `https://*.example.com`.
- `credentials`: bool-like.
- `age`: max age seconds.
- `methods`: array or comma-separated string. Defaults to `GET, POST, PUT, PATCH, DELETE, OPTIONS`.
- `headers`: array or comma-separated string. Defaults to common JSON/AJAX/auth/CSRF headers.

Behavior:

- If no `Origin`, pass through without headers.
- If origin not allowed, pass through without headers.
- Preflight:
  - Requires `OPTIONS`, `Origin`, and `Access-Control-Request-Method`.
  - Validates requested method and requested headers.
  - Returns `403` for invalid preflight.
  - Returns `204` with `Access-Control-Allow-*` headers for valid preflight.
- Normal CORS:
  - Calls `$next($request)`.
  - Converts non-Response results to `Response`.
  - Adds `Access-Control-Allow-Origin`.
  - Adds `Access-Control-Allow-Credentials: true` only when enabled.
  - Adds `Vary: Origin` when allowed origin is not `*`.
- Does not send `Access-Control-Allow-Credentials: false`.
- `Access-Control-Max-Age`, allow methods, and allow headers are sent only for preflight.

### CSRF

Base class: `Spark\Foundation\Http\Middlewares\CsrfProtection`

It is abstract. Applications may extend and set protected `$except`.

Behavior:

- `skip()` matches request paths against `$except`; supports wildcard patterns.
- Ensures a session CSRF token and `XSRF-TOKEN` cookie exist.
- Validates only unsafe/postback methods: `POST`, `PUT`, `PATCH`, `DELETE`.
- Accepts `_token` post field, `X-XSRF-TOKEN`, or `X-CSRF-TOKEN`.
- Header token may be plain session token or encrypted token.
- Invalid/missing token throws `InvalidCsrfTokenException`.
- Application maps that exception to HTTP 419.

API routes are configured by `Application::withRouting()` to exclude `csrf` by default.

### Throttle

Base class: `Spark\Foundation\Http\Middlewares\ThrottleIncomingRequests`

Parameter order:

```text
throttle:{attempts},{minutes},{suffix}
```

Defaults:

- attempts: `50`
- minutes: `1`
- suffix: `''`

Behavior:

- Uses client IP, request method, request path, and suffix to build a cache key.
- Stores timestamps in `Cache('th:requests')`.
- Rejects when request count within window is >= attempts.
- Throws `TooManyRequests`, mapped by `Application` to HTTP 429.
- Uses configured cache driver, so throttle storage follows `config('cache')`.

## Authentication and Authorization

Main classes:

- `Spark\Http\Auth`
- `Spark\Http\Gate`
- `Spark\Http\Session`

Auth:

- Configured through `config('auth', ...)`.
- Uses a model class, defaulting from config or constructor.
- Tracks user id and user cache.
- Supports session/cookie auth, JWT auth, basic auth, and custom auth drivers.
- Public API includes:
  - `user($key = null, $default = null)`
  - `id()`, `getId()`, `hasId()`
  - `attempt($credentials)`
  - `login(Model $user, bool $remember = false)`
  - `logout()`
  - `check()`, `isGuest()`, `isLogged()`
  - `getJwtToken()`, `createJwtToken()`
  - `refresh()`, `clearCache()`

Gate:

- Define abilities with `define($ability, $callback)`.
- Add hooks with `before()` and `after()`.
- Check with `allows()`, `denies()`, `any()`, `none()`.
- Enforce with `authorize()`, which throws `AuthorizationException`.

Session:

- Starts only in web mode and only if headers are not already sent.
- Static methods for `get`, `set`, `put`, `forget`, `flush`, `pull`, `invalidate`, `regenerate`, `destroy`, `id`, `flash`, `getFlash`, `clearFlash`, `all`, `close`.

## Database

Main classes:

- `Spark\Database\DB`
- `Spark\Database\QueryBuilder`
- `Spark\Database\Model`
- `Spark\Database\Migration`
- `Spark\Database\Schema\Schema`
- `Spark\Database\Schema\Blueprint`
- `Spark\Database\Casts\Castable`
- `Spark\Database\Casts\Attribute`

### DB

`DB` is a PDO wrapper.

Config:

- Reads `config('database')` when constructed without explicit config.
- Supports a `connections` map.
- Driver key chooses a matching connection; fallback is `default`.
- Normalizes:
  - `username` -> `user`
  - non-sqlite `database` -> `name`
  - sqlite `file` or `path` -> `database`

Driver helpers:

- `getDriver()`
- `isMySQL()`
- `isSQLite()`
- `isPostgreSQL()`
- `isDriver()`

Common methods:

- `getPdo()`
- `query()`
- `statement()`
- `prepare()`
- `exec()`
- `resetConfig()`
- `resetPdo()`

### Query Builder

`QueryBuilder` is chainable and also used behind `Model::__callStatic`.

Core API includes:

- `table()`, `from()`, `as()`, `select()`, `selectRaw()`, `column()`
- `where()`, `orWhere()`, `notWhere()`, raw wheres, grouped wheres
- `whereNull`, `whereIn`, `between`, `like`, contains/starts/ends
- JSON helpers such as `findInJson`, `whereJsonContains`
- joins and join conditions
- `orderBy`, `orderAsc`, `orderDesc`, `groupBy`, `having`
- `limit`, `offset`, `take`, `skip`
- `insert`, `bulkUpdate`, `update`, `delete`, `truncate`
- `first`, `firstOrFail`, `last`, `all`, `get`, `paginate`
- `value`, `pluck`, aggregates, `count`, `exists`, `notExists`
- `updateOrInsert`, `create`, `increment`, `decrement`
- `toSql()`, `clone()`, `copy()`

It handles bindings and named parameters internally. Prefer builder APIs over raw SQL unless raw SQL is needed.

### Model

`Model` is an active-record-like base class.

Important properties for subclasses:

```php
protected string $table;
protected string $primaryKey;
protected array $fillable = [];
protected array $guarded = [];
protected array $hidden = [];
protected array $appends = [];
protected array $casts = [];

protected const USE_TIMESTAMPS = true;
protected const CREATED_AT = 'created_at';
protected const UPDATED_AT = 'updated_at';
```

Behavior:

- Table defaults to snake plural class basename.
- Primary key defaults to `id`.
- `query()` returns a `QueryBuilder` for the model table and fetches model instances.
- `fill()` updates attributes and applies casts.
- `save()` inserts or updates depending on primary key, applies casts, and manages timestamps when enabled.
- Timestamp-enabled models should treat `created_at` and `updated_at` as datetime values.
- Model supports array access, magic properties, `toArray()`, `toJson()`, visibility, appends, dirty tracking, original tracking, nested attribute tracking, event hooks, and relations.
- Dynamic static calls forward to query builder, for example `User::where(...)->first()`.

Model creation helpers:

- `create()`
- `createOrUpdate()`
- `firstOrCreate()`
- `firstOrNew()`

Casts:

- Native casts: `int`, `integer`, `real`, `float`, `double`, `decimal`, `string`, `bool`, `boolean`, `object`, `array`, `json`, `collection`, `date`, `datetime`, `timestamp`, `encrypted`, `hashed`.
- Custom cast classes implement `Spark\Database\Contracts\CastsAttributes`.
- `Attribute::make($get, $set)` supports accessor/mutator style casts.

### Schema and Migrations

Schema:

- `Schema::create($table, fn(Blueprint $table) => ...)`
- `Schema::table($table, fn(Blueprint $table) => ...)`
- `Schema::drop()`, `dropIfExists()`, `rename()`
- `hasTable()`, `hasColumn()`, `hasColumns()`, `getColumnListing()`
- Foreign key constraint toggles.

Blueprint supports many Laravel-like methods:

- `id`, `increments`, `bigIncrements`
- integer variants
- `string`, `char`, `text`, `longText`
- `decimal`, `double`, `float`
- `boolean`, `enum`, `json`
- `date`, `dateTime`, `time`, `timestamp`
- `timestamps`, `nullableTimestamps`, `softDeletes`, `rememberToken`
- indexes: `primary`, `unique`, `index`, `fullText`, `spatialIndex`
- foreign keys: `foreignId`, `nullableForeignId`, `foreign`, `constrained`
- alter helpers: `dropColumn`, `dropIndex`, `dropForeign`, `renameColumn`

Migration:

- `Migration` runs migration files from a migrations folder.
- Tracks applied migrations.
- Supports `up`, `down`, and `refresh`.

## Cache

Main class: `Spark\Utils\Cache`

Driver source: `config('cache.driver')`

Drivers:

- `sqlite`
- `redis`

SQLite:

- Uses `cache.connections.sqlite.path`.
- If path is directory-like, creates `{md5(name)}.cache` under that directory.
- Creates a `caches` table with key, data, created_at, expire_at.
- Uses WAL and performance pragmas.

Redis:

- Uses `cache.connections.redis`.
- Uses `RedisConnector`.
- Key prefix shape includes configured prefix, `cache`, and md5 cache name.

API:

- `Cache::make($name)`
- `has($key, $eraseExpired = false)`
- `store($key, $data, $expire = null)`
- `load($key, $callback, $expire = null)`
- `retrieve($keyOrKeys, $eraseExpired = false)`
- `metadata($key)`
- `retrieveAll()`
- `erase($keyOrKeys)`
- `eraseExpired()`
- `getExpired()`
- `flush()`, `clear()`, `flushIf()`
- `storeMany()`, `storeManyWithExpiry()`
- `increment()`, `decrement()`
- `add()`
- `remember()`
- `pull()`
- `ttl()`
- `stats()`
- `optimize()`
- array access methods

Helpers:

```php
cache('default')->store('key', 'value', '+5 minutes');
$value = cache('default')->retrieve('key');
```

## Lock

Main class: `Spark\Utils\Lock`

Driver source: `config('cache.driver')`

Lock intentionally shares cache config, not queue config.

SQLite:

- Uses `cache.connections.sqlite.lock_path` if present, else `path`.
- Directory-like paths create `{md5(name)}.lock`.

Redis:

- Uses `cache.connections.redis`.
- Prefix shape includes configured prefix, `lock`, and md5 lock namespace.

API:

- `Lock::make($name)`
- `lock($key, $timeout = 10, $waitTimeout = 5)`
- `unlock($key)`
- `unlockAll()`
- `isLocked($key)`
- `ownsLock($key)`
- `releaseExpiredLocks()`
- `extendLock($key, $additionalSeconds)`
- `withLock($key, $callback, $timeout = 10, $waitTimeout = 5)`
- `getLockOwner()`
- `getLockInfo($key)`
- `forceUnlock($key)`
- `optimize()`
- array access methods

Helper:

```php
lock('critical-section', timeout: 10, waitTimeout: 5);
lock(name: 'default')->withLock('critical-section', fn() => do_work());
```

## Queue

Main classes:

- `Spark\Queue\Queue`
- `Spark\Queue\Job`

Driver source: `config('queue.driver')`

Drivers:

- `sqlite`
- `redis`

SQLite:

- Uses `queue.connections.sqlite.path`.
- Directory-like paths create `jobs.db`.
- Stores jobs, statuses, attempts, failure reasons, repeat schedules.

Redis:

- Uses `queue.connections.redis`.
- Uses Redis hashes and sorted sets for pending/reserved/failed/repeated jobs.
- Prefix shape includes configured prefix and queue namespace.

Application integration:

```php
app()->withQueue(
    jobs: [
        job([TaskRunner::class, 'handle'])->repeatEveryMinutes(5),
    ],
    log: true
);
```

`Application::withQueue()` creates a queue singleton and pushes configured jobs with `pushOnce()`.

Job API:

- `Job::make($callback, $parameters = [], $queue = 'default', $metadata = [])`
- `repeat($repeat)`
- `repeatEveryMinutes($minutes = 1)`
- `repeatHourly()`, `repeatDaily()`, `repeatWeekly()`, `repeatMonthly()`
- `schedule($time)`
- `delay($seconds)`
- `handle()`
- `dispatch($queue = 'default')`
- `dispatchOnce($queue = 'default')`
- metadata getters, display name, failure information

Queue API:

- `push($job, $queue = 'default')`
- `pushOnce($job, $queue = 'default')`
- `work($queue = 'default', ...)`
- `getJobs()`
- `getFailedJobs()`
- `retryFailedJobs()`
- `clearAllJobs()`
- `clearRepeatedJobs()`
- `clearFailedJobs()`
- `removeJobById()`
- `removeQueue()`
- `logging()`

Important:

- `pushOnce()` prevents duplicate scheduled/repeated jobs using queue-specific uniqueness/fingerprint behavior.
- Use it for scheduler/cron-style jobs registered in `bootstrap/app.php`.
- Do not mix cache and queue config: queue has its own `config('queue')`.

## Redis Connector

Main class: `Spark\Utils\RedisConnector`

Shared by cache, lock, and queue.

Config supports:

- `host`
- `port`
- `password`
- `database`
- `prefix`
- `timeout`
- `read_timeout`
- `retry_interval`
- `persistent`
- `persistent_id`
- `username`
- `url`
- `options`

`resolveConnectionConfig()` merges defaults, parses `url` when present, normalizes values, and returns a connection-ready array.

## Views

Main classes:

- `Spark\View\Blade`
- `Spark\View\BladeCompiler`
- `Spark\View\Attributes`

Blade:

- Default view path and cache path come from helpers/config conventions.
- Supports `render`, `include`, `component`, sections, layouts, composers, shared data, custom directives, attribute compilation, and cache clearing.
- Helper `view($template, $context)` returns a `Response` when a template is passed, or the Blade instance when no template is passed.
- Helper `fireline($template, $context)` returns a Fireline-style response.

Blade compiler supports:

- `@extends`, `@section`, `@yield`, includes
- component tags
- echo compilation
- PHP blocks
- custom directives
- compiled path expiration and cache clearing

## Console and Commands

Main classes:

- `Spark\Console\Console`
- `Spark\Console\Commands`
- `Spark\Foundation\Providers\ConsoleServiceProvider`
- `Spark\Foundation\Console\PrimaryCommandsHandler`
- `Spark\Foundation\Console\MakeStubCommandsHandler`

Commands:

- Registered in `Commands`.
- Console parses CLI args and executes registered callback.
- `ConsoleServiceProvider` registers core commands and stubs.
- Helpers include `command()`.

Common command families include:

- cache/config/view clearing
- app key generation
- migration commands
- queue commands
- make stubs for controllers, models, migrations, middleware, requests, jobs, providers, casts, seeders, views

## Events

Main class: `Spark\Events`

API:

- `addListener()`
- `dispatch()`
- `dispatchWithResponse()`
- `dispatchIf()`
- `dispatchUnless()`
- `once()`
- `removeListener()`
- `until()`
- `halt()`
- `subscribe()`
- `flush()`

Application debug lifecycle events include:

- `app:booting`
- `app:booted`
- `app:routeMatched`
- `app:middlewaresHandled`
- `app:routeDispatched`
- `app:routeFallback`
- `app:terminated`

## Facades

Base: `Spark\Facades\Facade`

Facades resolve services from `Application::$app->make(static::getFacadeAccessor())` and forward static calls to instance methods/macros.

Available facade classes include:

- `App`
- `Auth`
- `Blade`
- `Cache`
- `DB`
- `Event`
- `Gate`
- `Hash`
- `Http`
- `Lock`
- `Log`
- `Mail`
- `Route`

Use facades when the framework style already uses them. In lower-level classes, direct dependency/container resolution is often clearer.

## Helpers

Helpers are globally loaded from `src/Foundation/helpers.php`.

Important helpers:

- Container: `app`, `get`, `has`, `bind`, `singleton`, `call`
- Request/response: `request`, `response`, `json`, `redirect`, `to_route`, `back`
- Routing: `router`, `route_url`, `route`
- Database: `database`, `db`, `query`, `connect_db`
- Views: `view`, `blade`, `fireline`
- URL/assets: `url`, `home_url`, `asset_url`, `asset`, `media_url`, `media`, `request_url`
- Paths: `root_dir`, `resource_dir`, `app_dir`, `storage_dir`, `lang_dir`, `upload_dir`, `views_dir`, `temp_dir`, `dir_path`
- Config/env: `config`, `env`, `envs`, `env_parse_value`, `normalize_env_numeric_value`, `env_string_value`
- Auth/gate: `auth`, `user`, `is_guest`, `is_logged`, `can`, `canAny`, `cannot`, `authorize`, `gate`
- CSRF: `csrf_token`, `csrf`, `method`
- Events/jobs: `event`, `job`, `dispatch`
- Cache/lock: `cache`, `unload_cache`, `lock`
- I18n: `__`, `_e`
- Frontend: `vite`
- Validation/input: `input`, `validator`, `errors`, `old`
- Utilities: `cookie`, `mailer`, `abort`, `hashing`, `hasher`, `passcode`, `bcrypt`, `encrypt`, `decrypt`, `http`, `image`, `paginator`, `uploader`, `now`, `carbon`, `filemanager`, `fm`, `arr_from_set`, `tracer`, `is_cli`, `is_web`, `is_debug_mode`, `tracer_log`, `pipeline`, `concurrency`

Path rule:

- Use `storage_dir()` for app storage paths.
- Use `dir_path()` to normalize paths.
- Do not assume missing legacy helpers exist.

## Error Handling

`abort($error, $message = null, $code = null)` renders framework error responses.

Common exception mapping in `Application::run()`:

- `RouteNotFoundException` -> 404
- `ItemNotFoundException` -> 404
- `NotFoundException` -> 404
- `AuthorizationException` -> 403
- `InvalidCsrfTokenException` -> 419
- `TooManyRequests` -> 429

Unhandled exceptions go to `Tracer`.

## Coding Conventions for This Framework

Follow these rules when modifying TinyCore:

1. Prefer existing framework patterns over Laravel assumptions.
2. Do not edit `src/Support` unless explicitly requested.
3. Keep helper APIs stable and avoid adding broad global helpers unless necessary.
4. Use the container and config system consistently.
5. For cache, lock, and queue, use current config shapes:
   - cache/lock -> `config('cache')`
   - queue -> `config('queue')`
   - database -> `config('database')`
6. Preserve sqlite and redis parity for cache, lock, and queue changes.
7. For request lifecycle changes, check all of:
   - `Application`
   - `Router`
   - `Middleware`
   - `Request`
   - `Response`
   - built-in middleware
8. For model/database changes, check:
   - `Model`
   - `QueryBuilder`
   - `DB`
   - casts
   - schema grammar when SQL changes are involved
9. Avoid destructive git operations. This repo may have staged and unstaged user work.
10. Run `php -l` on touched PHP files.
11. Run focused smoke tests with `php -r` when practical.
12. Run `git diff --check` before finalizing.

## Current Production-Sensitive Behavior to Preserve

Request lifecycle:

- Middleware wraps route responses, not just early returns.
- CORS preflight must not run non-OPTIONS route callbacks.
- Explicit `OPTIONS` routes must still work.
- `HEAD` should match `GET`.
- Router should allow optional trailing slash.

CORS:

- Do not send `Access-Control-Allow-Credentials: false`.
- Do not send preflight-only headers on normal responses.
- Validate requested method and requested headers on preflight.
- Wildcard origin patterns must use escaped wildcard replacement correctly.

Config:

- Config cache must refresh when `.env` changes.
- Config cache should include config file mtimes and env signature.

Model timestamps:

- Timestamp-enabled models should parse `created_at` and `updated_at` as datetime-like values by default, while respecting explicit model casts.

Queue:

- `pushOnce()` must be consistent for sqlite and redis.
- Repeated jobs should be safe for scheduler/cron registration.

Throttle:

- Parameters are `attempts, minutes, suffix`.
- Storage uses `Cache`, so it follows cache driver config.

## Suggested Verification Commands

Syntax:

```sh
php -l src/Foundation/Application.php
php -l src/Routing/Router.php
php -l src/Http/Middleware.php
php -l src/Foundation/Http/Middlewares/CorsAccessControl.php
php -l src/Foundation/Http/Middlewares/CsrfProtection.php
php -l src/Foundation/Http/Middlewares/ThrottleIncomingRequests.php
```

Diff hygiene:

```sh
git diff --check
git diff --cached --check
```

Search:

```sh
rg "pattern" src -g '!src/Support/**'
rg --files -g '!src/Support/**'
```

## AI Agent Checklist Before Editing

Before changing code:

1. Read the target file and its neighboring lifecycle files.
2. Search for existing usage with `rg`.
3. Identify whether the change affects public API, config shape, lifecycle behavior, storage drivers, or helper behavior.
4. Avoid touching staged/unrelated user work.

Before final response:

1. Run `php -l` on changed PHP files.
2. Run a focused runtime smoke test if behavior changed.
3. Run `git diff --check`.
4. Summarize changed files and verification.
5. Mention anything not tested.

