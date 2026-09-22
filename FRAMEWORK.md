# TinyMVC Development Skill — Framework Reference

Use this reference when implementing, debugging, reviewing, or testing a TinyMVC/Spark application. The project-local skill entry point is [.agents/skills/tinymvc-development/SKILL.md](.agents/skills/tinymvc-development/SKILL.md). It selects the relevant parts of this file; reading the entire reference is unnecessary for a small task.

This reference describes the current TinyCore implementation, including soft-delete scopes and the separate `upsert()` arguments. Check the application's installed source before using a newer API. A Composer constraint such as `^3.0` does not prove which implementation is installed.

## Select the Relevant Reference

| Task | Read these sections |
| --- | --- |
| New application feature or unfamiliar project | [Development workflow](#how-to-use-this-file), [layout](#typical-application-layout), [bootstrap](#core-bootstrap) |
| Endpoint, validation, or permissions | [Routing](#routing), [controllers](#controllers), [requests](#requests-and-validation), [authorization](#auth-and-authorization) |
| Data model or database query | [Models](#models), [relationships](#relationships), [query builder](#query-builder) |
| Schema or many-to-many table | [Migrations and schema](#migrations-and-schema) |
| Trash, restore, or permanent deletion | [Soft deletes](#soft-deletes), [testing](#testing) |
| Browser interface | [Views](#views), [frontend integrations](#frontend-integrations), [middleware](#middleware) |
| Background or shared work | [Queue and jobs](#queue-and-jobs), [cache](#cache), [locks](#locks) |
| Framework extension | [Providers](#service-providers), [events](#events), [commands](#console-commands), [source lookup](#framework-source-lookup-paths) |
| Verification or regression | [Testing](#testing), [verification](#verification-checklist-for-ai-agents) |

## What TinyMVC Is

TinyMVC is a small PHP framework powered by the TinyCore package.

- Core package: `tinymvc/tinycore`
- Core namespace: `Spark\\`
- Minimum PHP: `8.2`
- Style: Laravel-like ergonomics, custom implementation
- Dependency injection: `Spark\Foundation\Application` extends `Spark\Container`
- Routing: `Spark\Http\Routing\Router`
- HTTP: `Spark\Http\Request`, `Spark\Http\Response`, `Spark\Http\Middleware`
- Database: PDO wrapper, query builder, active-record-like models, schema/migrations
- Storage utilities: sqlite or redis cache, lock, and queue

Do not assume Laravel classes such as `Illuminate\Http\Request`, `Illuminate\Support\Facades\Route`, `artisan`, Eloquent, or Laravel middleware internals exist. Use the `Spark\\` classes and global helpers documented here.

## Framework Source Lookup Paths

In an application project, TinyCore source is normally installed under:

```text
./vendor/tinymvc/tinycore/
```

Resolve the application's installed version from `composer.lock` / Composer installed metadata and read the matching implementation. Source code takes precedence over old examples and docblocks. In this multi-repository development workspace, `../tinycore/src` is a separate core checkout; use it when working on TinyCore itself, not as proof that an application's installed package has the same behavior.

Treat installed vendor files as reference during application tasks. If the user requests a core change, edit the actual TinyCore checkout and verify the application's dependency separately. Do not silently change dependency versions or patch vendor code to make an example work.

Core bootstrap and container:

- Application lifecycle: `./vendor/tinymvc/tinycore/src/Foundation/Application.php`
- Application contract: `./vendor/tinymvc/tinycore/src/Contracts/ApplicationContract.php`
- Service container and dependency injection: `./vendor/tinymvc/tinycore/src/Container.php`
- Service provider base class: `./vendor/tinymvc/tinycore/src/Foundation/Providers/ServiceProvider.php`
- Core console provider: `./vendor/tinymvc/tinycore/src/Foundation/Providers/ConsoleServiceProvider.php`
- Environment and config cache: `./vendor/tinymvc/tinycore/src/DotEnv.php`
- Global helpers: `./vendor/tinymvc/tinycore/src/Foundation/helpers.php`

Routing and request lifecycle:

- Router: `./vendor/tinymvc/tinycore/src/Http/Routing/Router.php`
- Route builder: `./vendor/tinymvc/tinycore/src/Http/Routing/Route.php`
- Route groups: `./vendor/tinymvc/tinycore/src/Http/Routing/RouteGroup.php`
- Resource routes: `./vendor/tinymvc/tinycore/src/Http/Routing/RouteResource.php`
- Route facade: `./vendor/tinymvc/tinycore/src/Facades/Route.php`
- Request: `./vendor/tinymvc/tinycore/src/Http/Request.php`
- Response: `./vendor/tinymvc/tinycore/src/Http/Response.php`
- Middleware pipeline: `./vendor/tinymvc/tinycore/src/Http/Middleware.php`

Built-in middleware:

- CORS base middleware: `./vendor/tinymvc/tinycore/src/Foundation/Http/Middlewares/CorsAccessControl.php`
- CSRF base middleware: `./vendor/tinymvc/tinycore/src/Foundation/Http/Middlewares/CsrfProtection.php`
- Throttle base middleware: `./vendor/tinymvc/tinycore/src/Foundation/Http/Middlewares/ThrottleIncomingRequests.php`
- Middleware contract: `./vendor/tinymvc/tinycore/src/Contracts/Http/MiddlewareInterface.php`

Validation, auth, and session:

- Form request base class: `./vendor/tinymvc/tinycore/src/Foundation/Http/FormRequest.php`
- Validator: `./vendor/tinymvc/tinycore/src/Http/Validator.php`
- Validated input wrapper: `./vendor/tinymvc/tinycore/src/Http/Input.php`
- Input errors: `./vendor/tinymvc/tinycore/src/Http/InputErrors.php`
- Auth manager: `./vendor/tinymvc/tinycore/src/Http/Auth.php`
- Gate/authorization: `./vendor/tinymvc/tinycore/src/Http/Gate.php`
- Session: `./vendor/tinymvc/tinycore/src/Http/Session.php`

Database, ORM, and migrations:

- DB/PDO wrapper: `./vendor/tinymvc/tinycore/src/Database/DB.php`
- Query builder: `./vendor/tinymvc/tinycore/src/Database/QueryBuilder.php`
- Read/write/condition methods: `./vendor/tinymvc/tinycore/src/Database/Query/`
- Soft deletes: `./vendor/tinymvc/tinycore/src/Database/Concerns/InteractsWithSoftDeletes.php`
- ORM, relation subqueries, and pivots: `./vendor/tinymvc/tinycore/src/Database/Concerns/`
- Model callbacks: `./vendor/tinymvc/tinycore/src/Database/Events.php`
- DB facade transactions: `./vendor/tinymvc/tinycore/src/Facades/DB.php`
- Model base class: `./vendor/tinymvc/tinycore/src/Database/Model.php`
- Model casts trait: `./vendor/tinymvc/tinycore/src/Database/Casts/Castable.php`
- Attribute cast helper: `./vendor/tinymvc/tinycore/src/Database/Casts/Attribute.php`
- Migration runner: `./vendor/tinymvc/tinycore/src/Database/Migration.php`
- Schema facade/class: `./vendor/tinymvc/tinycore/src/Database/Schema/Schema.php`
- Blueprint: `./vendor/tinymvc/tinycore/src/Database/Schema/Blueprint.php`
- Column definitions: `./vendor/tinymvc/tinycore/src/Database/Schema/Column.php`
- Schema grammar: `./vendor/tinymvc/tinycore/src/Database/Schema/Grammar.php`
- Relations: `./vendor/tinymvc/tinycore/src/Database/Relation/`

Cache, lock, queue, and redis:

- Cache: `./vendor/tinymvc/tinycore/src/Cache/Cache.php`
- Lock: `./vendor/tinymvc/tinycore/src/Cache/Lock.php`
- Cache and lock contracts: `./vendor/tinymvc/tinycore/src/Cache/Contracts/`
- Cache storage drivers: `./vendor/tinymvc/tinycore/src/Cache/Storage/`
- Queue: `./vendor/tinymvc/tinycore/src/Queue/Queue.php`
- Queue storage drivers: `./vendor/tinymvc/tinycore/src/Queue/Storage/`
- Job wrapper: `./vendor/tinymvc/tinycore/src/Queue/Job.php`
- Class job dispatch trait: `./vendor/tinymvc/tinycore/src/Queue/Dispatchable.php`
- Fluent pending dispatch: `./vendor/tinymvc/tinycore/src/Queue/PendingDispatch.php`
- Job contracts: `./vendor/tinymvc/tinycore/src/Queue/Contracts/`
- Redis connector: `./vendor/tinymvc/tinycore/src/Utils/RedisConnector.php`

Views, console, events, facades, utilities, testing:

- Blade renderer: `./vendor/tinymvc/tinycore/src/View/Blade.php`
- Blade compiler: `./vendor/tinymvc/tinycore/src/View/BladeCompiler.php`
- View attributes: `./vendor/tinymvc/tinycore/src/View/Attributes.php`
- Console runner: `./vendor/tinymvc/tinycore/src/Console/Console.php`
- Command registry: `./vendor/tinymvc/tinycore/src/Console/Commands.php`
- Console stubs: `./vendor/tinymvc/tinycore/src/Foundation/Console/stubs/`
- Migration/pivot generators: `./vendor/tinymvc/tinycore/src/Foundation/Console/MakeStubCommandsHandler.php`
- Event dispatcher: `./vendor/tinymvc/tinycore/src/Events.php`
- Facade base class: `./vendor/tinymvc/tinycore/src/Facades/Facade.php`
- All facades: `./vendor/tinymvc/tinycore/src/Facades/`
- Carbon-like date utility: `./vendor/tinymvc/tinycore/src/Carbon.php`
- Mail utility: `./vendor/tinymvc/tinycore/src/Utils/Mail.php`
- HTTP client: `./vendor/tinymvc/tinycore/src/Http/Client/`
- Upload/file/image utilities: `./vendor/tinymvc/tinycore/src/Storage/Uploader.php`, `./vendor/tinymvc/tinycore/src/Utils/FileManager.php`, `./vendor/tinymvc/tinycore/src/Utils/Image.php`
- Tracer/debugging: `./vendor/tinymvc/tinycore/src/Tracer.php`
- Vite integration: `./vendor/tinymvc/tinycore/src/Utils/Vite.php`
- Unit/Feature Testing: `vendor/tinymvc/tinycore/src/Testing/ApplicationTestCase.php`, `vendor/tinymvc/tinycore/src/Testing/Assert.php`, `vendor/tinymvc/tinycore/src/Testing/TestCase.php`

## How To Use This File

1. Identify whether the user is changing an application, TinyCore, an optional integration, or documentation. Follow the existing app's choices and the user's requested scope.
2. Inspect the relevant entry points: `composer.json`, installed package version, `bootstrap/app.php`, the matching routes/controller/model, and nearby tests. Check `package.json` only when frontend work is involved. Read only the configuration needed for the task; do not dump `.env` secrets.
3. Choose the matching sections above. For uncertain signatures or side effects, inspect the installed method body, its traits, and the app's own wrappers. Method names resembling Laravel are not evidence of identical behavior.
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

## Typical Application Layout

Actual apps may vary, but common TinyMVC app layout is:

```text
app/
  Http/
    Controllers/
    Middlewares/
    Requests/
  Models/
  Providers/
  Jobs/
  Services/
bootstrap/
  app.php
  middlewares.php
  providers.php
  helpers.php
config/
  app.php
  cache.php
  database.php
  mail.php
  queue.php
database/
  migrations/
public/
  index.php
resources/
  views/
routes/
  web.php
  api.php
  webhook.php
  console.php
storage/
  cache/
  logs/
  queue/
  temp/
  uploads/
```

Always verify the actual project before creating files.

## Core Bootstrap

TinyMVC apps usually bootstrap the framework through `Spark\Foundation\Application`.

Example shape:

```php
<?php

use Spark\Foundation\Application;

return Application::create(
    path: dirname(__DIR__),
    config: 'config',
    providers: require __DIR__ . '/providers.php',
)
    ->withMiddleware(
        load: __DIR__ . '/middlewares.php',
        queue: ['csrf']
    )
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        webhook: __DIR__ . '/../routes/webhook.php'
    );
```

Important lifecycle:

1. `Application` sets `Application::$app`.
2. `.env` is loaded and cached.
3. Core services are registered.
4. Config is discovered and cached.
5. Providers register and boot.
6. Router dispatches the current `Request`.
7. Middleware wraps the matched route.
8. Route callback/controller returns a value.
9. Router converts it to `Response`.
10. `Response::send()` sends headers and body.
11. `Application::terminate()` finishes the client response and runs callbacks registered with `defer()`.

Post-response work:

```php
defer(function () use ($userId) {
    app(App\Services\Analytics::class)->trackSignup($userId);
});

app()->defer(function (App\Services\AuditLog $audit) use ($order) {
    $audit->recordOrderViewed($order->id);
});
```

Use `defer()` for small post-response tasks such as audit logging, analytics, cleanup, or lightweight notifications. Deferred callbacks are invoked through the container, so type-hinted dependencies can be injected. They run after the response is sent, in registration order. A deferred callback may register another deferred callback; it will run in the same termination cycle after the callbacks that were already in the queue.

Important defer notes:

- Deferred callbacks are not a replacement for durable queues; use Queue jobs for work that must survive process crashes, timeouts, or worker restarts.
- `Application::terminate()` calls `fastcgi_finish_request()` or `litespeed_finish_request()` when available, otherwise it flushes output buffers.
- `defer()` registers a shutdown fallback so callbacks can still run when code sends a response and exits early.
- Exceptions thrown by deferred callbacks are reported/logged and do not stop later deferred callbacks.
- In debug mode, `app:terminated` is dispatched during application termination.

## Configuration

Config files return PHP arrays. Use `config('key.path')` and `env('KEY', $default)`.

### Database Config

Expected shape:

```php
<?php

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

### Cache and Lock Config

Cache and lock both use `config('cache')`.

```php
<?php

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

### Queue Config

Queue uses `config('queue')`.

```php
<?php

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

## Global Helpers

Common helpers:

```php
app();                 // application container
app(Foo::class);       // resolve from container
get(Foo::class);       // resolve from container
config('app.debug');
config(['app.debug' => true]);
env('APP_KEY');

request();
request('email');
response('OK', 200);
json(['ok' => true]);
redirect('/login');
back();
defer(fn() => tracer_log('response_sent'));

router();
route_url('users.show', ['id' => 5]);
route('users.show', ['id' => 5]); // returns Spark\Url

view('users.index', ['users' => $users]);
fireline('emails.welcome', ['user' => $user]);

auth();
user();
gate();
authorize('update-post', $post);

db();
query('users');
cache('default');
lock('key');

storage_dir('cache');
root_dir('routes/web.php');
dir_path($path);

now();
carbon('2026-01-01');
abort(404, 'Not found');
tracer_log('message');
```

Use helpers only when they already match the app style. In service classes, dependency injection is often cleaner.

## Routing

Routes are usually written in `routes/web.php`, `routes/api.php`, or `routes/webhook.php`.

`router()` returns the router. `route()` builds a named-route URL; use the route facade or `router()` to register endpoints:

```php
use Spark\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
Route::post('/users', [UserController::class, 'store'])->middleware('auth');
Route::put('/users/{id}', [UserController::class, 'update']);
Route::patch('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
```

Supported route methods:

```php
Route::get($path, $callback);
Route::post($path, $callback);
Route::put($path, $callback);
Route::patch($path, $callback);
Route::delete($path, $callback);
Route::options($path, $callback);
Route::any($path, $callback);
Route::match(['GET', 'POST'], $path, $callback);
Route::view('/about', 'pages.about');
Route::fireline('/email-preview', 'emails.welcome');
Route::inertia('/contact', 'Contact', ['key' => 'value']); // Requires the Inertia adapter/provider
Route::redirect('/old', '/new', 301);
Route::fallback(fn() => response('Not found', 404));
```

Route parameters:

```php
Route::get('/posts/{id}', fn(int $id) => "Post $id");
Route::get('/posts/{id?}', fn(?string $id = null) => $id);
Route::get('/files/*', fn() => 'wildcard');
```

Route groups:

```php
use App\Http\Controllers\Api\UserController;

Route::group(['prefix' => 'admin', 'middleware' => ['auth']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::group(['prefix' => 'api', 'middleware' => ['cors'], 'withoutMiddleware' => ['csrf']], function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

Controller grouping:

```php
Route::group(['prefix' => 'users', 'callback' => UserController::class], function () {
    Route::get('/', 'index')->name('users.index');
    Route::post('/', 'store')->name('users.store');
    Route::get('/{id}', 'show')->name('users.show');
});
```

Resource routes:

```php
Route::resource('/posts', PostController::class, name: 'posts');
```

Resource route method map:

- `GET /posts` -> `index`
- `GET /posts/create` -> `create`
- `POST /posts` -> `store`
- `GET /posts/{id}` -> `show`
- `GET /posts/{id}/edit` -> `edit`
- `PUT/PATCH /posts/{id}` -> `update`
- `DELETE /posts/{id}` -> `destroy`

## Controllers

Generated controller stubs usually extend an app-level `Controller` class. Follow existing app convention.

Example API controller:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controller;
use App\Models\Post;
use Spark\Http\Request;

class PostController extends Controller
{
    public function index(): array
    {
        return [
            'data' => Post::latest()->take(20)->all(),
        ];
    }

    public function show(int $id): array
    {
        $post = Post::findOrFail($id);

        return ['data' => $post];
    }

    public function store(Request $request): \Spark\Http\Response
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $post = Post::create($data);

        return json(['data' => $post], 201);
    }

    public function update(int $id, Request $request): array
    {
        $post = Post::findOrFail($id);
        $post->fill($request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]));
        $post->save();

        return ['data' => $post];
    }

    public function destroy(int $id): \Spark\Http\Response
    {
        Post::findOrFail($id)->remove();

        return response('', 204);
    }
}
```

Route callbacks and controller methods can return:

- `Spark\Http\Response`
- string
- integer HTTP status code
- array
- object castable to string
- `Arrayable`

Arrays are JSON encoded by `Response::send()`. The controller example shows the request/persistence shape; apply the app's authentication middleware and ownership authorization before changing private records.

## Requests and Validation

Base request: `Spark\Http\Request`

Form request: `Spark\Foundation\Http\FormRequest`

Form requests validate immediately in the constructor:

```php
<?php

namespace App\Http\Requests;

use Spark\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'published' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A title is required.',
        ];
    }
}
```

Use in a controller:

```php
public function store(StorePostRequest $request)
{
    $data = $request->validated()->toArray();
}
```

Common validation rules:

- `required`, `required_if`, `required_unless`
- `present`, `filled`, `nullable`
- `email`, `url`
- `string`, `text`, `char`
- `numeric`, `number`, `int`, `integer`
- `array`, `list`
- `min`, `max`, `size`, `between`
- `same`, `confirmed`
- `in`, `not_in`
- `regex`
- `unique`, `exists`, `not_exists`
- `boolean`, `float`, `decimal`
- `alpha`, `alpha_num`, `alpha_dash`
- `digits`, `digits_between`, `min_digits`, `max_digits`
- `date`, `date_format`, `before`, `after`
- `json`, `ip`, `ipv4`, `ipv6`, `mac_address`, `uuid`
- `lowercase`, `uppercase`
- `starts_with`, `ends_with`, `contains`, `not_contains`
- `accepted`, `declined`, `prohibited`
- `file`, `image`, `mimes`
- `password`

Request input helpers:

```php
$request->query('page', 1);
$request->post('email');
$request->input('email');
$request->only(['name', 'email']);
$request->except(['password']);
$request->safe('body', ['p', 'strong']);
$request->input()->boolean('published'); // Input wrapper; this does not validate the field
```

## Middleware

Middleware implements `Spark\Contracts\Http\MiddlewareInterface`.

```php
<?php

namespace App\Http\Middlewares;

use Spark\Contracts\Http\MiddlewareInterface;
use Spark\Http\Request;

class EnsureAdmin implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        if (!auth()->check() || !auth()->user('is_admin')) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
```

Register aliases in `bootstrap/middlewares.php`:

```php
<?php

return [
    'auth' => App\Http\Middlewares\Authenticate::class,
    'admin' => App\Http\Middlewares\EnsureAdmin::class,
    'csrf' => App\Http\Middlewares\VerifyCsrfToken::class,
    'cors' => App\Http\Middlewares\Cors::class,
    'throttle' => App\Http\Middlewares\ThrottleRequests::class,
];
```

Attach middleware:

```php
Route::get('/admin', [AdminController::class, 'index'])->middleware(['auth', 'admin']);
Route::post('/webhook', [WebhookController::class, 'store'])->withoutMiddleware('csrf');
Route::get('/limited', fn() => 'ok')->middleware('throttle:60,1,api');
```

Middleware parameters are parsed after `:`, comma-separated.

Middleware can wrap responses:

```php
public function handle(Request $request, \Closure $next): mixed
{
    $response = $next($request);

    if ($response instanceof \Spark\Http\Response) {
        $response->setHeader('X-App', 'TinyMVC');
    }

    return $response;
}
```

## Built-In Middleware Base Classes

### CORS

Extend `Spark\Foundation\Http\Middlewares\CorsAccessControl`.

```php
<?php

namespace App\Http\Middlewares;

use Spark\Foundation\Http\Middlewares\CorsAccessControl;

class Cors extends CorsAccessControl
{
    protected array $config = [
        'origin' => ['https://example.com', 'https://*.example.com'],
        'credentials' => true,
        'age' => 600,
        'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'headers' => ['Content-Type', 'Authorization', 'X-XSRF-TOKEN'],
    ];
}
```

Behavior:

- Normal requests get CORS headers after the route response.
- Valid preflight returns `204`.
- Invalid preflight returns `403`.
- Wildcard origins such as `https://*.example.com` are supported.
- `credentials => true` reflects concrete origins instead of using `*`.

### CSRF

Extend `Spark\Foundation\Http\Middlewares\CsrfProtection`.

```php
<?php

namespace App\Http\Middlewares;

use Spark\Foundation\Http\Middlewares\CsrfProtection;

class VerifyCsrfToken extends CsrfProtection
{
    protected array $except = [
        'webhook/*',
    ];
}
```

CSRF validates `POST`, `PUT`, `PATCH`, and `DELETE`. It accepts `_token`, `X-CSRF-TOKEN`, or `X-XSRF-TOKEN`. Invalid tokens throw an exception mapped to HTTP 419.

### Throttle

Extend `Spark\Foundation\Http\Middlewares\ThrottleIncomingRequests`.

```php
<?php

namespace App\Http\Middlewares;

use Spark\Foundation\Http\Middlewares\ThrottleIncomingRequests;

class ThrottleRequests extends ThrottleIncomingRequests
{
}
```

Use as:

```php
Route::get('/api/search', [SearchController::class, 'index'])
    ->middleware('throttle:100,1,search');
```

Parameter order is `attempts, minutes, suffix`.

## Models

Models extend `Spark\Database\Model`.

```php
<?php

namespace App\Models;

use Spark\Database\Model;

class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = ['title', 'body', 'published', 'meta', 'published_at'];

    protected array $casts = [
        'published' => 'boolean',
        'meta' => 'array',
        'published_at' => 'datetime',
    ];
}
```

Defaults:

- Table defaults to snake plural class name if not set.
- Primary key defaults to `id`.
- Timestamps are enabled by default with `created_at` and `updated_at`.
- Timestamp columns are date/datetime parsed when timestamps are enabled.

Mass assignment:

- `fill()` stores supplied attributes; `$fillable` / `$guarded` filter the persistence data. A nonempty fillable list takes precedence.
- With no fillable list, guarded names exclude exact fields. `['*']` is not a wildcard guard in this implementation. Empty guarded/fillable lists allow all fields.
- Validate input and authorize ownership before `create()` or `fill()`. Prefer an explicit fillable list; attributes retained on an object may still be serialized even when excluded from persistence.

Create/update (`$validated` is the result of request validation):

```php
$post = Post::create([
    'title' => $validated->get('title'),
    'body' => $validated->get('body'),
]);

$post = Post::findOrFail($id);
$post->fill($validated);
$post->save();

$post->remove();
```

Querying:

```php
$posts = Post::where('status', 'published')
    ->latest()
    ->take(10)
    ->all();

$post = Post::where('slug', $slug)->first();
$post = Post::findOrFail($id);
$exists = Post::where('email', $email)->exists();
```

Casts:

- `int`, `integer`
- `float`, `double`, `real`
- `decimal:2`
- `string`
- `bool`, `boolean`
- `array`, `json`, `object`
- `collection`
- `date`, `datetime`, `timestamp`
- `encrypted`
- `hashed`
- custom cast class implementing `Spark\Database\Contracts\CastsAttributes`

Accessors/mutators use the classic methods:

```php
use Spark\Database\Model;

class User extends Model
{
    public function getNameAttribute($value): string
    {
        return trim((string) $value);
    }

    public function setNameAttribute($value): string
    {
        return trim((string) $value);
    }
}
```

The getter controls access; the setter runs when preparing storage data, not immediately on assignment. Prefer these or a custom cast over the current `<name>Attribute(): Attribute` dispatch path, which reads a property instead of calling that method. Recheck that implementation when upgrading.

Custom casts implement `get($value)` and `set($value)` with no model/key/context parameters and are constructed without arguments. Null passes through; `decimal:2` produces a formatted string, and date/datetime/timestamp casts produce `Spark\Carbon` values. Hashed values are one-way; encrypted values depend on the application key.

Model behavior to preserve:

- `find()` / `first()` return a model or false; the `OrFail` variants throw. `all()` returns an array, `get()` a Collection, and `save()` / `remove()` booleans.
- Disable timestamps with `protected const USE_TIMESTAMPS = false`; otherwise create both timestamp columns. `CREATED_AT` / `UPDATED_AT` rename them.
- `getChanges()` contains original values of changed fields; read current attributes for new values. Dirty tracking needs a persisted baseline.
- `copy()` clones the primary key too; it is not row replication. `only()` / `except()` return projected model objects, not plain arrays.
- Hidden fields still win over `makeVisible()`. Appended values are added after filtering; only append public output.
- Override protected `events(): Spark\Database\Events` for `created`, `updated`, `deleted`, and `changed` callbacks. They receive no arguments; use `$this`. Bulk builder writes do not dispatch per-row callbacks, and callbacks are not deferred until commit.
- Add public `scopePublished(QueryBuilder $query)` methods for reusable conditions, then call `Post::published()`. Unknown builder methods may execute a query and forward to a Collection.

## Relationships

Declare public relationship methods using the model's protected helpers:

```php
public function posts(): \Spark\Database\Relation\HasMany
{
    return $this->hasMany(Post::class, foreignKey: 'user_id');
}

public function roles(): \Spark\Database\Relation\BelongsToMany
{
    return $this->belongsToMany(Role::class, table: 'roles_users',
        foreignPivotKey: 'user_id', relatedPivotKey: 'role_id');
}
```

Other helpers are `hasOne`, `belongsTo`, and `hasManyThrough`. Specify keys for custom schemas. `$user->posts` loads/caches results; `$user->posts()` returns a relation for query chaining. Use `User::with('posts')->all()` to avoid one query per parent. `with()` does not filter parent rows; `whereHas()` does, and both may be needed.

- Nested eager loading: `with('posts.comments')` works directly; a keyed callback on that path constrains comments. Keep primary/foreign keys when selecting columns.
- `load()` uses the lazy path and respects cached results / `lazy: false`. Use `with()` or static `loadRelations()` for explicit eager loading; `unsetRelation()` / `reloadRelations()` manage cached results.
- `hasMany()->create()` / `save()` assign the parent key; persist the parent first and allow the foreign key in the child fillable list. `hasOne` needs a unique database constraint for enforced one-to-one cardinality.
- `belongsTo()->associate()` / `dissociate()` change the child object; call `save()` to persist.
- Pivot operations: `attach`, `detach`, `sync`, `syncWithoutDetaching`, `toggle`, `updateExistingPivot`. `sync()` returns attached/detached IDs; update existing pivot attributes explicitly. `detach()` without IDs removes all associations for the parent, not related records. Wrap multi-step changes in a transaction when required.
- Pivot table defaults use sorted plural table names, e.g. `roles_users`. Configure `withPivot()` / `wherePivot()` before query execution and reload cached relations after mutations.
- `withCount('posts')` adds `posts_count`; `withSum('posts', 'views')` adds `posts_sum`. Supply aliases to avoid collisions. Use `has()` for parent count filtering; extra comparison arguments on `withCount()` do not implement that filtering.
- `morphWith()` uses an explicit map such as `['post' => ['class' => Post::class, 'relations' => ['user']]]`; do not assume `morphTo()` / `morphMany()` helpers exist.
- Related reads, direct relation `count()`, `has()` / `whereHas()`, and `withCount()` / other aggregates apply the related model's soft-delete scope by default. Use `withTrashed()` / `onlyTrashed()` in each related-query callback to override it. Parent scopes do not propagate to children; see [Soft deletes](#soft-deletes) for joined-table boundaries.

## Query Builder

Use `query($table)` or model static calls.

```php
$users = query('users')
    ->where('active', true)
    ->orderDesc('id')
    ->take(20)
    ->all();

$id = query('users')->insert([
    'name' => 'Jane',
    'email' => 'jane@example.com',
]);

query('users')->where('id', $id)->update(['active' => false]);
query('users')->where('id', $id)->delete();
```

Common methods:

- `table`, `from`, `select`, `selectRaw`, `column`
- `where`, `orWhere`, `whereRaw`, `grouped`
- `whereNull`, `whereIn`, `between`, `like`
- `whereDate`, `whereYear`, `whereMonth`
- JSON helpers
- joins
- `orderBy`, `orderAsc`, `orderDesc`
- `limit`, `offset`, `take`, `skip`
- `first`, `firstOrFail`, `last`, `all`, `get`, `paginate`
- `value`, `pluck`, `count`, `exists`, `doesntExist`
- `insert`, `insertOrIgnore`, `insertOrReplace`, `upsert`, `update`, `delete`, `forceDelete`, `restore`, `truncate`
- `updateOrInsert`, `increment`, `decrement`
- `toSql`

Prefer builder methods over string SQL. Bind values; allowlist dynamic column names, sort directions, and SQL expressions. `select()` accepts an array/string or multiple columns. Repeated `orderBy()` calls replace ordering; use a trusted `orderByRaw()` for multiple sort columns.

### Upserts and return values

```php
query('products')->upsert(
    [
        ['sku' => 'SPARK-01', 'name' => 'Starter', 'price' => 25],
        ['sku' => 'SPARK-02', 'name' => 'Team', 'price' => 50],
    ],
    conflict: ['sku'],
    update: ['name', 'price'],
);
```

The signature is `upsert($data, ?array $conflict = null, ?array $update = null)`: separate arrays, not the old combined config array. Null conflict defaults to `['id']`; null/omitted/empty update selects all supplied non-conflict columns. Add a matching unique constraint. MySQL uses actual unique indexes, while SQLite/PostgreSQL use the conflict target. Insert variants return an integer ID, not an affected-row count. PostgreSQL reads it from RETURNING and returns zero for inserts without a numeric primary key; ignored inserts return zero. `update()` / `delete()` / `forceDelete()` return affected-row counts; builder `restore()` returns bool.

`firstOrCreate()` / `updateOrInsert()` are lookup-then-write operations; use unique constraints for concurrent inserts. `insertOrReplace()` emits replacement SQL on MySQL/SQLite and throws on PostgreSQL; use explicit upsert conflict columns there. There is no declared `bulkUpdate()` method.

### State, pagination, and write boundaries

Use a fresh builder for each operation or `copy()` before execution. Retrieval resets query state; writes clear conditions/bindings. A method forwarded to Collection loads results into memory, so do not assume `chunk()` is database streaming. Mapper callbacks receive the whole result array, not a single row.

`paginate($limit = 10, $keyword = 'page', $fields = null)` uses the query-string page and returns `Spark\Utils\Paginator`. Bound the page size and sort consistently. `items()`, `total()`, `page()`, and `pages()` expose data/metadata. Filter before pagination; grouped `count()` counts groups, and distinct/union totals are preserved. Pagination counts the result before applying limit/offset on every driver.

`update()`, `delete()`, `forceDelete()`, and builder `restore()` require a WHERE condition or an explicit trash scope on a soft-delete model. A bare default model query does not satisfy that guard. Increment/decrement can affect every row in scope; `truncate()` physically empties the whole table regardless of trash scope. Plain table queries do not apply model casts, lifecycle callbacks, or archive filtering.

For nontrivial JSON/date-part SQL, inspect the driver-specific implementation. JSON helpers use field/key/value and text matching on all three drivers; use dot-separated object paths for portable cases. Date-part helpers extract DATE/YEAR/MONTH on all three drivers. Test on the production driver when depending on these differences.

### Connections and transactions

```php
use Spark\Facades\DB;

$result = DB::transaction(function () use ($userId) {
    return DB::table('posts')->where('user_id', $userId)->update(['published' => true]);
});
```

The facade helper commits the callback result or rolls back/rethrows an exception. It uses the application connection, creates savepoints for nested calls, and has no retry loop. Never manually finish/reconnect the transaction or use implicitly committing DDL inside a callback. `Spark\Database\DB::connection($configOrName)` creates a separate wrapper; use its `table()` and transaction methods consistently. Creating another wrapper does not move models or Schema to it. Schema refreshes its cached PDO/grammar when the application DB is replaced. `connect_db()` returns a builder. `reset()` / `resetPdo()` replace connection state and must not interrupt a transaction.

## Migrations and Schema

Migration files return an anonymous class with `up()` and `down()`.

```php
<?php

use Spark\Database\Schema\Blueprint;
use Spark\Database\Schema\Schema;

return new class {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

Useful blueprint methods:

- `id`, `increments`, `bigIncrements`
- integer variants
- `string`, `char`, `text`, `longText`
- `decimal`, `double`, `float`
- `boolean`, `enum`, `json`
- `date`, `dateTime`, `time`, `timestamp`
- `timestamps`, `nullableTimestamps`, `softDeletes`, `rememberToken`
- `foreignId`, `foreign`, `constrained`
- `primary`, `unique`, `index`, `fullText`, `spatialIndex`
- `dropColumn`, `dropIndex`, `dropForeign`, `renameColumn`

Column modifiers:

```php
$table->string('email')->unique();
$table->text('body')->nullable();
$table->boolean('active')->default(true);
$table->timestamp('published_at')->nullable();
$table->foreignId('user_id', nullable: true)->constrained()->setNullOnDelete(); // default: nullable=false
```

### Generating and applying migrations

```bash
php spark make:migration create_posts_table
php spark make:migration --pivot
php spark migrate
php spark migrate:rollback --step=1
```

`--pivot` (alias `-p`) prompts for the first and second related table names. `users` and `roles` produce a `roles_users` migration with an `id`, `user_id`, `role_id`, and cascading foreign keys. Generation writes a migration file; `php spark migrate` applies it. Related tables must exist first. Add a composite unique constraint yourself when duplicate associations are invalid.

The runner records applied filenames in `database/migrations.json`. Preserve that file with its database. Files use `migration_` / `seed_` prefixes; `php spark make:seeder Name` and `php spark migrate --seed` handle seed files. `migrate:fresh` rolls back recorded migrations and replays them; it is destructive, not a read-only verification command.

Create new migrations for deployed schemas instead of rewriting history. Failures can leave partial DDL because the runner does not automatically wrap each file in a transaction. On SQLite, adding/removing foreign keys or primary keys from existing tables needs a deliberate rebuild; there is no `change()` column modifier. Most type methods do not imply NOT NULL: call `required()` for required fields. Use `nullable: true` on `foreignId()` before `setNullOnDelete()`.

## Soft Deletes

### Model and schema setup

Add a nullable deletion column and enable the model constant:

```php
use Spark\Database\Schema\Blueprint;
use Spark\Database\Schema\Schema;

// In a new migration for an existing table:
Schema::table('posts', function (Blueprint $table) {
    $table->softDeletes();
});
```

```php
namespace App\Models;

use Spark\Database\Model;

class Post extends Model
{
    protected const USE_SOFT_DELETES = true;
    protected string $table = 'posts';
    protected array $fillable = ['title', 'body', 'user_id'];
    protected array $casts = ['deleted_at' => 'datetime'];
}
```

`softDeletes()` takes no arguments and returns void; do not chain modifiers. The current Model already includes the soft-delete trait, attaches itself to queries, and composes scoped WHERE clauses. No application compatibility override or Laravel trait is needed. For an older installed package, verify these capabilities before generating code that relies on them.

Apply the column migration before enabling the model. A rollback uses `$table->dropColumn('deleted_at')`; remove the model behavior before dropping the column. For a custom column, set `SOFT_DELETE_COLUMN = 'archived_at'`, cast it if needed, and create `$table->timestamp('archived_at')->nullable()`.

### Select, archive, restore, and purge

```php
$active = Post::orderDesc('id')->all();
$trash = Post::onlyTrashed()->orderDesc('id')->paginate(20);
$all = Post::withTrashed()->all();
$activeAgain = Post::withTrashed(false)->all();

$post = Post::findOrFail($id);
$post->remove();
$archived = Post::onlyTrashed()->findOrFail($id);
$isArchived = $archived->trashed();
$restored = $archived->restore();

$restoredAny = Post::onlyTrashed()->where('user_id', $userId)->restore();
$purged = Post::onlyTrashed()->where('user_id', $userId)->forceDelete();
```

| Call on a soft-delete model | Effect |
| --- | --- |
| Normal read / `withoutTrashed()` / `withTrashed(false)` | Active rows only |
| `onlyTrashed()` | Archived rows only |
| `withTrashed()` | Active and archived rows |
| `where(...)->delete()` | Archives matching active rows by default |
| `onlyTrashed()->delete()` | Re-stamps archived rows; does not physically delete them |
| `withTrashed()->delete()` | Stamps both active and archived rows |
| `onlyTrashed()->restore()` / `withTrashed()->restore()` | Restores archived rows, optionally narrowed by WHERE |
| `withoutTrashed()->restore()` | No changes: selection contains only active rows |
| `onlyTrashed()->forceDelete()` | Permanently deletes the entire trash |
| `withTrashed()->forceDelete()` | Permanently deletes all rows |
| `withoutTrashed()->forceDelete()` | Permanently deletes active rows |
| `where('id', $id)->forceDelete()` | Deletes that ID whether active or archived |
| Bare `Post::query()->delete()` / `forceDelete()` / `restore()` | Returns zero/false because there is no explicit selection |

The last trash-scope call wins and keeps existing WHERE conditions. An explicit scope satisfies the bulk-write guard; it is not ownership authorization. Add account/tenant conditions before archive/restore/purge operations that belong to one user.

`remove()` and both restore entry points return bool; builder delete/forceDelete return affected-row counts. Call bulk restore on a builder, since `Post::restore()` collides with the non-static model method. A loaded `$post->forceDelete()` is constrained by its primary key. Deletion does not update a previously loaded object's deletion timestamp: re-fetch with `onlyTrashed()` / `withTrashed()` before `trashed()`. Ordinary `refresh()` cannot fetch archived records.

### Relationship and database boundaries

- Enable soft deletes independently on each related model. `$user->posts()->onlyTrashed()->forceDelete()` and `restore()` retain the parent foreign-key condition.
- Eager loading, relationship existence checks, and aggregates all use the related model's prepared scope. `User::has('posts')` ignores archived posts; `doesntHave('posts')` includes users whose posts are all archived. `withCount('posts')` counts active posts; `withCount('posts as all_posts', fn($q) => $q->withTrashed())` includes archives. Custom deletion-column names work too.
- Set archive scopes independently on parent and related queries, and on each loading/filtering/aggregate operation. `User::withTrashed()->with('posts')` still loads only active posts. Nested paths scope every model; a `whereHas('posts.comments', $callback)` callback changes only the deepest (`comments`) query. Use nested callbacks to override intermediate scopes.
- A `belongsTo` owner hidden by its soft-delete scope loads as null; include it explicitly with `with(['user' => fn($q) => $q->withTrashed()])` when needed. Relationship-definition callbacks apply to lazy/eager reads, but existence/aggregate queries use only their own supplied callback.
- Automatic deletion predicates are driver-quoted and qualified using the read alias or actual table name. Qualify user-written join conditions separately. Writes target the physical table and do not emit read aliases; use unaliased write conditions.
- `hasManyThrough()` filters both the final model and a soft-deletable intermediate model by default. `withTrashedParents()` includes archived intermediates; `withTrashedParents(false)` restores their active-only scope. It works on the relation definition, direct relation, or loading/existence/aggregate callback. `withTrashed()` controls final records independently. Custom intermediate deletion columns are respected.
- Plain pivot tables have no model scope. When links deliberately have a deletion column, declare `->wherePivotNull('deleted_at')` on the relation; it applies to lazy/eager reads, `has()`, and aggregates. A separate `wherePivotNotNull()` relation can read archived links. Neither `withTrashed()` nor `withTrashedParents()` removes a pivot condition. `detach()` / `sync()` still physically delete links; this filter does not add a pivot archive lifecycle.
- Arbitrary `join()` calls do not infer soft-delete settings for other tables. Supply explicit conditions for those joins; ordinary pivot tables without deletion columns need no change.
- `query('posts')`, raw SQL, and non-soft-delete models have no automatic archive behavior. Trash switches on those builders do not authorize unfiltered writes.
- Soft deletion is an update, so it does not trigger foreign-key delete cascades or release a normal unique constraint. Decide explicitly whether a conflicting archived record should be restored.
- Bulk writes do not fire callbacks once per row or cascade archive/restore operations. There are no dedicated restoring/restored callbacks in the current model event set.

### Regression cases for a trash feature

Use an isolated database with active and archived rows belonging to at least two owners. Verify ordinary reads hide archived rows, trash reads hide active rows, restore clears the timestamp, and permanent deletion removes only the selected rows. Check wrong-owner IDs, repeated operations, custom columns if used, relationship boundaries, and explicit scope switching. Use actual affected-row/state assertions, not just a successful HTTP status.

## Auth and Authorization

Auth helper:

```php
auth()->attempt(['email' => $email, 'password' => $password]);
auth()->login($user, remember: true);
auth()->logout();
auth()->check();
auth()->isGuest();
auth()->isLogged();
auth()->user();
auth()->id();
```

Gate:

```php
gate()->define('update-post', function ($post) {
    $user = auth()->user();
    return $user !== null && (string) $post->user_id === (string) $user->id;
});

if (can('update-post', $post)) {
    // allowed
}

authorize('update-post', $post); // throws AuthorizationException on deny
```

`AuthorizationException` is mapped to HTTP 403. Gate forwards only the supplied arguments; it does not automatically inject the authenticated user. Read `auth()->user()` in the callback or pass the user explicitly.

## Cache

Use:

```php
cache('default')->store('key', $value, '+10 minutes');
$value = cache('default')->retrieve('key');
$value = cache('default')->remember('key', fn() => expensive(), '+10 minutes');
cache('default')->erase('key');
cache('default')->flush();
```

Cache driver is configured by `config('cache.driver')`.

SQLite cache:

- `cache.connections.sqlite.path` can be a directory.
- The cache class creates one sqlite cache file per cache name.

Redis cache:

- Uses `cache.connections.redis`.
- Uses configured prefix.

## Locks

Use locks for critical sections.

```php
lock(name: 'default')->withLock('invoice:' . $invoiceId, function () use ($invoice) {
    // critical work
}, timeout: 10, waitTimeout: 5);
```

Or:

```php
$lock = lock(name: 'default');

if ($lock->lock('report:daily', 30, 5)) {
    try {
        // work
    } finally {
        $lock->unlock('report:daily');
    }
}
```

Lock driver follows cache config.

## Queue and Jobs

Prefer class jobs for application work. A class job implements `Spark\Queue\Contracts\JobInterface` and usually uses `Spark\Queue\Dispatchable`.

```php
<?php

namespace App\Jobs;

use Spark\Queue\Contracts\JobContract;
use Spark\Queue\Contracts\JobInterface;
use Spark\Queue\Dispatchable;
use Throwable;

class SendWelcomeEmail implements JobInterface
{
    use Dispatchable;

    public int $tries = 3;

    public array $backoff = [120, 300];

    public function __construct(private int $userId)
    {
    }

    public function handle(): void
    {
        // send email
    }

    public function failed(JobContract $job, Throwable $exception): void
    {
        // called only after the queue exhausts all tries
    }
}
```

Class job dispatch:

```php
use App\Jobs\SendWelcomeEmail;
use App\Jobs\SyncReports;

SendWelcomeEmail::dispatch($userId)->onQueue('emails');
SendWelcomeEmail::dispatch($userId)->onQueue('emails')->delay(60);

SyncReports::dispatchOnce()
    ->onQueue('reports')
    ->repeatEveryMinutes(5);
```

`Dispatchable::dispatch(...$arguments)` passes arguments to the job constructor. The queue worker later calls `handle()` through the application container.

Per-job retry policy can override the worker defaults:

```php
class SyncReports implements JobInterface
{
    use Dispatchable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900];

    public function handle(): void
    {
        // sync reports
    }
}
```

Retry policy notes:

- `$tries` overrides the `Queue::work(tries: ...)` value for that job.
- `$backoff` overrides the `Queue::work(delay: ...)` value for retry scheduling.
- `$backoff` values are seconds.
- Array backoff is selected by failed attempt number; extra attempts reuse the last value.
- Use lowercase `$backoff`, not `$backOff`.
- Invalid or missing values fall back to the worker defaults.

The fluent dispatch object supports:

- `onQueue('name')`
- `once()` for duplicate-safe push behavior
- `delay($seconds)`
- `schedule($time)`
- `repeat($intervalOrAlias)`
- `repeatEveryMinutes($minutes)`
- `repeatHourly()`, `repeatDaily()`, `repeatWeekly()`, `repeatMonthly()`
- `send()` or `dispatch()` to push immediately

The pending dispatch is also pushed automatically when the fluent expression falls out of scope, so this is valid:

```php
SendWelcomeEmail::dispatch($userId)->onQueue('emails');
```

The older job wrapper API is still valid and useful in `bootstrap/app.php`:

```php
job(App\Jobs\SendWelcomeEmail::class)->dispatch('emails');
job(App\Jobs\SyncReports::class)->repeatEveryMinutes(5)->dispatchOnce('reports');
```

In `bootstrap/app.php`, recurring jobs should be registered with `withQueue()` so they use queue `pushOnce()` behavior and do not duplicate every bootstrap:

```php
->withQueue(
    jobs: [
        job(App\Jobs\SyncReports::class)->repeatEveryMinutes(5),
    ]
)
```

`withQueue()` accepts `jobs` and an optional `then` callback. Queue logging options were removed from the public queue API; do not pass `log: true`, call `Queue::logging()`, or depend on `storage/logs/queue.log`.

Repeat constants live on `Spark\Queue\Job`:

```php
use Spark\Queue\Job;

job(App\Jobs\SyncReports::class)->repeat(Job::REPEAT_DAILY);
job(App\Jobs\SyncReports::class)->repeat('weekly'); // alias for Job::REPEAT_WEEKLY
```

Supported repeat aliases:

- `hourly` -> `Job::REPEAT_HOURLY`
- `daily` -> `Job::REPEAT_DAILY`
- `weekly` -> `Job::REPEAT_WEEKLY`
- `biweekly` -> `Job::REPEAT_BIWEEKLY`
- `monthly` -> `Job::REPEAT_MONTHLY`
- `quarterly` -> `Job::REPEAT_QUARTERLY`
- `yearly` -> `Job::REPEAT_YEARLY`

Queue driver is configured by `config('queue.driver')`.

Important:

- Use `dispatchOnce()` or `withQueue(jobs: [...])` for scheduler/cron-style repeated jobs.
- Public job properties `$tries` and `$backoff` override worker retry defaults when present.
- Job `failed()` hooks are called by `Queue` only after all tries are exhausted, not on every retryable exception.
- A `failed()` method may accept either `Throwable $exception` or `JobContract $job, Throwable $exception`.
- Queue connection/driver comes from `config('queue')`; do not invent Laravel-style `onConnection()` usage.
- Queue has separate config from cache.
- Redis and sqlite drivers should behave consistently for push/pushOnce/work.

## Views

Return views from routes/controllers:

```php
return view('posts.index', ['posts' => $posts]);
```

Blade-like templates live under `resources/views` in many apps.

Common view helpers:

```php
view('template.name', $context);
blade()->render('template.name', $context);
Blade::share('key', $value);
```

Use existing app template style. TinyCore has its own Blade-like compiler, not full Laravel Blade.

## Frontend Integrations

Inspect `package.json`, `vite.config.js`, existing templates/pages, and registered providers before choosing an approach. The skeleton uses Vite, Tailwind, Alpine, and Blade; optional packages may add different capabilities.

| Existing stack | Development approach |
| --- | --- |
| Blade + Alpine | Server-rendered views with targeted browser interactions; preserve CSRF form handling |
| FireLine | Follow the installed integration's navigation/form conventions and current app templates |
| Inertia PHP + React/Vue | Verify the adapter/provider and matching client version, then return the app's existing Inertia responses |
| Orbit | Extend its existing administration resources, BREAD, access rules, and React/shadcn components rather than creating a parallel admin architecture |

`Route::inertia()` needs the adapter service provider; a route method name alone does not install the integration. Inspect the installed package README/source for version-sensitive props and APIs. Do not add FireLine, Inertia, React, Vue, or Orbit unless the app/task calls for that integration. Use the app's Vite asset helper and build configuration instead of hardcoded development-server URLs.

## Responses and Redirects

```php
return response('Saved', 200);
return json(['saved' => true], 201);
return redirect('/login');
return to_route('posts.show', ['id' => $post->id]);
return back()->withErrors(['email' => 'Invalid'])->withInput();
return response('', 204);
```

For APIs, returning arrays is acceptable because `Response::send()` JSON encodes arrays.

For explicit JSON status codes, prefer `json($data, $status)`.

## Files and Uploads

Use `disk()` / `Spark\Facades\Disk` for storage that may be local, public, or S3-compatible. Configuration is in `config/disk.php`; `FILESYSTEM_DISK` chooses the default. The concrete class is `Spark\Storage\Disk`.

```php
$disk = disk('public');
$disk->put('reports/result.txt', 'Ready');
$contents = $disk->get('reports/result.txt');
$path = $disk->uploader('avatars', extensions: ['jpg', 'png'], maxSize: 2048)
    ->upload($request->file('avatar'));
$url = $disk->url($path);
```

- `local` defaults to private `storage/app`; `public` uses `storage/uploads` and the existing `storage:link` mapping. Keys are relative paths, never URLs or absolute paths. Traversal is rejected; local child symlinks are not followed.
- Use `putFile($directory, $localPath)` or `putFileAs($directory, $localPath, $name)` for trusted existing local files. These return keys and preserve the source. HTTP uploads must use genuine PHP upload files; size checks use their actual size, not a submitted size value.
- Disk-backed uploaders retain extension/size validation, image resizing, variants, and cleanup. Their driver destinations and returned paths are disk-relative. Staging lives under private `storage/temp/disk-uploads`.
- `Spark\Storage\S3UploaderDriver` implements the existing `UploaderUtilDriverInterface` and delegates to `Spark\Storage\S3Storage`. Configure AWS or compatible endpoints, region, bucket, key/secret, optional session token, optional public/CDN URL, and path-style addressing. ACLs are omitted by default; use `acl: public-read` only for an ACL-enabled public bucket.
- `exists`, `missing`, `get`, `put`, `copy`, `move`, `delete`, `files`, `allFiles`, `size`, `mimeType`, and `lastModified` share the disk API. Storage failures throw; missing-file deletion succeeds. Arrays and moves are not atomic. Local copies stream; S3 copies run on the server and preserve object metadata.
- `path()` is local-only. `url()` requires a configured URL on local disks and does not grant public access on S3. `temporaryUrl($key, $secondsOrDateTime)` is S3-only, signs the origin, and allows 1–604800 seconds. Authorize private downloads first.
- S3 uses SigV4 with cURL; listings use SimpleXML. Single PUTs are limited to 5 GiB; multipart uploads, bucket management, and automatic role-credential discovery/refresh are not implemented. Validate behavior and policy on the selected provider.
- Config files load before application config is merged: use `dirname(__DIR__)` for filesystem defaults there, not `storage_dir()` / `media_url()`. Those helpers are available in running application code.


Request file helpers:

```php
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar');
    $request->moveFile('avatar', storage_dir('uploads/avatar.jpg'));
}
```

Utilities:

- `uploader()`
- `filemanager()` or `fm()`
- `image()`

Inspect existing app usage before implementing uploads.

## Mail and HTTP Client

Helpers/facades:

```php
mailer();
http();
```

HTTP client classes live under `Spark\Http\Client`.

Mail utility depends on optional `phpmailer/phpmailer`.

The Mail utility no longer exposes framework-specific logging methods. Use normal exception handling, `tracer_log()`, or your app logger around mail sending if mail activity needs to be recorded.

## Service Providers

Providers extend `Spark\Foundation\Providers\ServiceProvider`.

```php
<?php

namespace App\Providers;

use Spark\Foundation\Providers\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton(\App\Services\BillingService::class);
    }

    public function boot(): void
    {
        // boot code
    }
}
```

Register providers in `bootstrap/app.php` through `Application::create(... providers: [...])` or `withApp(providers: [...])`.

## Events

```php
event('order.created', $order);

app()->on('order.created', function ($order) {
    // handle event
});
```

The event dispatcher supports priorities, one-time listeners, dispatch with responses, `until`, and subscriptions.

## Console Commands

Command routes may be loaded through `withRouting(commands: __DIR__ . '/../routes/console.php')` from `bootstrap/app.php`.

Use the command registry:

```php
command('reports:sync', [ReportCommand::class, 'handle'])
    ->description('Sync reports');
```

Follow existing app command style.

## Facades

Available facades include:

```php
use Spark\Facades\App;
use Spark\Facades\Auth;
use Spark\Facades\Blade;
use Spark\Facades\Cache;
use Spark\Facades\DB;
use Spark\Facades\Event;
use Spark\Facades\Gate;
use Spark\Facades\Hash;
use Spark\Facades\Http;
use Spark\Facades\Lock;
use Spark\Facades\Mail;
use Spark\Facades\Route;
```

Facades resolve services from the application container. Use them only if the app already uses facade style or it improves clarity.

## Error Handling

Use:

```php
abort(404, 'Post not found');
abort(403, 'Forbidden');
```

Framework mappings:

- route not found -> 404
- not found/item not found -> 404
- authorization failure -> 403
- invalid CSRF -> 419
- too many requests -> 429

Register custom exception handlers with `withExceptions()` if the app uses that style.

## Common Feature Recipe

For a new API resource:

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
in the skeleton's `tests/`. In a TinyCore source checkout, `php tests/soft-delete-scopes.php`
runs the standalone SQLite scope/alias/relationship regression suite.

`APP_ENV=testing` must be set before creating a CLI application. In that mode,
`.env` and config caches are skipped; `Application::create()` merges
`tests/config.php` before provider registration. The feature base supplies a
temporary storage path; the supplied config uses in-memory SQLite. Middleware,
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
7. If changing queue/cache/lock, test sqlite default and consider redis parity.
8. Run `git diff --check`.
9. Run relevant tests with `php test --filter=Name` or `composer test -- --filter=Name`; run the full suite for shared behavior changes. Run `npm run build` when frontend assets change.
10. Mention anything not tested.

## Request Lifecycle Summary

TinyMVC request flow:

```text
public/index.php
  -> bootstrap/app.php
  -> Application
  -> DotEnv and config cache
  -> providers
  -> Request
  -> Router
  -> Middleware pipeline
  -> controller/callback
  -> Response
```

Use `Spark\\` classes, app namespaces, and the helpers in this file. When unsure, inspect nearby app files and follow the existing TinyMVC pattern.

## Storage contracts and compatibility

Storage implementations are `Spark\Storage\Disk`, `Spark\Storage\Uploader`, and `Spark\Storage\S3Storage`. The old `Spark\Disk` class is removed; update imports to `Spark\Storage\Disk`. The two old `Spark\Utils` names remain deprecated aliases. The uploader's existing contracts and exception namespace remain compatible.

Inject `Spark\Storage\Contracts\DiskContract` for application services. The application binds it to the default disk lazily, and default `Spark\Facades\Disk` calls resolve that binding. A provider may override it with a closure returning a selected disk. Named `disk()` calls and explicit `Disk::disk()` / `Disk::build()` construct disks directly.

S3 listing defaults to ListObjectsV2; configure `list_version: 1` only for legacy endpoints. Low-level `listFiles()` returns `next_marker`, which is an opaque continuation token for V2 and must be passed back unchanged. S3 copy is server-side, preserves metadata, and checks for error XML in successful HTTP responses before a move can delete its source. GET preserves content-encoded object bytes. Both upload and single copy are capped at 5 GiB; multipart operations are not implemented.

Keep S3 ACLs null for policy-controlled AWS buckets and R2. R2 uses region `auto` and a path-style account endpoint. Spaces uses a regional origin; MinIO needs the deployment's own origin/region/addressing settings. Current storage tests use local fixtures, not live cloud accounts. Run an authorized disposable-prefix smoke test against the deployment's actual bucket/policy before release.
