# Global Helpers and Facades

Read this reference for global helpers, static facades, and utility functions. Source paths and command working directories refer to the application root.

---

## Static Facades (`Spark\Facades\*`)

TinyCore provides first-class static facades under `Spark\Facades\*`. They provide clean, testable, static access to underlying services in the application container:

| Facade | Service / Class | Primary Use Case |
| --- | --- | --- |
| `Spark\Facades\App` | `Spark\Foundation\Application` | Service container binding, resolving, environment checks (`App::isProduction()`, `App::isTesting()`). |
| `Spark\Facades\Auth` | `Spark\Http\Auth` | User authentication, guards, credentials verification, tokens (`Auth::attempt()`, `Auth::user()`, `Auth::loginUsingId()`). |
| `Spark\Facades\Blade` | `Spark\View\Blade` | Template compilation, directive registration (`Blade::directive()`), component rendering. |
| `Spark\Facades\Cache` | `Spark\Cache\Cache` | Storing, retrieving, atomic locks, and cache invalidation (`Cache::remember()`, `Cache::make()`, `Cache::forget()`). |
| `Spark\Facades\DB` | `Spark\Database\DB` | Database transactions, connection switching, raw queries, table queries (`DB::transaction()`, `DB::table()`). |
| `Spark\Facades\Event` | `Spark\Events` | Event dispatching and listener registration (`Event::listen()`, `Event::dispatch()`). |
| `Spark\Facades\Gate` | `Spark\Http\Gate` | User authorization policies and ability checks (`Gate::define()`, `Gate::allows()`, `Gate::check()`, `Gate::authorize()`). |
| `Spark\Facades\Hash` | `Spark\Hash` | Password hashing (Bcrypt/Argon2) and verification (`Hash::make()`, `Hash::check()`). |
| `Spark\Facades\Http` | `Spark\Http\Client\Http` | Fluent outbound HTTP client with JSON handling and authentication (`Http::withToken()`, `Http::post()`). |
| `Spark\Facades\Lock` | `Spark\Cache\Lock` | Atomic distributed locking (`Lock::withLock()`, `Lock::make()`). |
| `Spark\Facades\Log` | `Spark\Log` | Application logging (`Log::info()`, `Log::error()`, `Log::warning()`). |
| `Spark\Facades\Mail` | `Spark\Utils\Mail` | Fluent email sending with Blade view rendering (`Mail::to()`, `Mail::make()`). |
| `Spark\Facades\Queue` | `Spark\Queue\Queue` | Background queue operations and inspection. |
| `Spark\Facades\Route` | `Spark\Http\Routing\Router` | Defining routes, route groups, resource routes, middleware. |
| `Spark\Facades\Schema` | `Spark\Database\Schema\Schema` | Database migrations and schema building (`Schema::create()`, `Schema::table()`). |
| `Spark\Facades\Session` | `Spark\Http\Session` | Web session manipulation (`Session::get()`, `Session::put()`, `Session::flash()`). |
| `Spark\Facades\Storage` | `Spark\Storage\Storage` | Filesystem storage (`Storage::disk('public')->put()`, `Storage::url()`). |
| `Spark\Facades\Validator` | `Spark\Http\Validator` | Explicit validator construction (`Validator::make($rules, $data)`). |

### Facade Code Examples

```php
use Spark\Facades\App;
use Spark\Facades\Auth;
use Spark\Facades\Cache;
use Spark\Facades\DB;
use Spark\Facades\Gate;
use Spark\Facades\Http;
use Spark\Facades\Lock;
use Spark\Facades\Log;
use Spark\Facades\Mail;
use Spark\Facades\Session;
use Spark\Facades\Storage;
use Spark\Facades\Validator;

// Application Environment & Container
if (App::isTesting()) {
    // Running within test suite
}
if (App::isDebugMode()) {
    // Debug mode is active
}
$service = App::make(PaymentService::class);

// Transactions
DB::transaction(function () {
    // Atomic multi-step database writes
});

// Cache with automatic computation
$stats = Cache::remember('site_stats', fn () => calculateStats(), '+30 minutes');

// Distributed Lock
Lock::withLock('order:123', function () {
    // Atomic work
}, timeout: 10, waitTimeout: 5);

// Outbound HTTP Client
$response = Http::withToken($token)
    ->timeout(10)
    ->post('https://api.example.com/items', $payload);

if ($response->successful()) {
    $itemId = $response->json('data.id');
}

// Mail
Mail::to($user->email, $user->name)
    ->subject('Welcome!')
    ->view('emails.welcome', ['user' => $user])
    ->send();

// Storage
Storage::disk('public')->put('documents/file.pdf', $fileContents);
$publicUrl = Storage::disk('public')->url('documents/file.pdf');

// Authorization & Policy Checks
Gate::authorize('update-post', $post); // Aborts with 403 if unauthorized
if (Gate::check('edit-post', $post)) {
    // User can edit
}

// Authentication
if (Auth::attempt($request->validated()->only('email', 'password'), $request->boolean('remember'))) {
    $user = Auth::user();
}

// Session
Session::put('theme', 'dark');
$theme = Session::get('theme', 'light');
Session::flash('status', 'Profile updated!');

// Logging
Log::info('Order transitioned to paid', ['order_id' => $order->id]);
Log::error('Payment gateway unreachable', ['error' => $e->getMessage()]);

// Manual Validator
$validator = Validator::make(['email' => 'required|email'], $request->all());
if ($validator->fails()) {
    // handle errors
}
```

---

## Modern Support Helpers

Spark includes a rich suite of modern Laravel-inspired support utilities:

### 1. Collections (`collect`) and Higher-Order Proxies

Create higher-order collections from arrays or models:

```php
$collection = collect([1, 2, 3, 4, 5])
    ->filter(fn ($n) => $n > 2)
    ->map(fn ($n) => $n * 10);

$users = User::latest()->get(); // Spark\Support\Collection
$names = $users->pluck('name');
$byRole = $users->groupBy('role');
$keyed = $users->keyBy('id');
```

#### Smart Higher-Order Collection Proxies

Spark collections support fluent Higher-Order Proxies, allowing you to access model properties or methods directly without verbose closures:

```php
// Property extraction / mapping:
$titles = $posts->map->title;               // Instead of: $posts->map(fn ($p) => $p->title)
$totalScore = $users->sum->score;           // Instead of: $users->sum(fn ($u) => $u->score)

// Filtering by boolean property:
$activeUsers = $users->filter->is_active;   // Instead of: $users->filter(fn ($u) => $u->is_active)

// Invoking methods across collection:
$orders->each->cancel();                    // Instead of: $orders->each(fn ($o) => $o->cancel())
```

### 2. Strings & Stringable (`str` and `Str`)

```php
use Spark\Support\Str;

// Fluent Stringable helper
$slug = str('Building Modern APIs with TinyMVC')->slug(); // 'building-modern-apis-with-tinymvc'
$headline = str('user_profile_photo')->headline();        // 'User Profile Photo'
$camel = str('created_at')->camel();                      // 'createdAt'

// Static Str methods
$uuid = Str::uuid();
$random = Str::random(32);
$contains = Str::contains($haystack, 'needle');
$startsWith = Str::startsWith($haystack, 'SPARK-');
```

### 3. Arrays (`Arr`) and Data Manipulation

```php
use Spark\Support\Arr;

// Get nested array value using dot notation
$value = Arr::get($array, 'user.profile.age', default: 18);
$filtered = Arr::only($array, ['id', 'name', 'email']);
$without = Arr::except($array, ['password', 'token']);
$flattened = Arr::dot($nestedArray);

// Global data helpers
$role = data_get($userOrArray, 'permissions.admin', default: false);
data_set($target, 'config.theme', 'dark');
```

### 4. Flow Control Helpers (`tap`, `throw_if`, `throw_unless`)

```php
// Tap into an object and return it
$user = tap(User::create($request->validated()), function (User $user) {
    SendAccountMail::dispatch($user->id)->onQueue('emails');
});

// Conditional exception throwing
throw_if(!Auth::check(), UnauthorizedException::class, 'User not authenticated.');
throw_unless($order->isPending(), DomainException::class, 'Order cannot be cancelled.');

// Value checks
if (blank($request->input('notes'))) {
    // null, empty string, or whitespace
}

if (filled($request->input('title'))) {
    // non-empty value
}
```

---

## Global Application Helpers

Common application helpers for quick operations:

```php
// Container & Config
app();
app(SomeService::class);
config('app.timezone', 'UTC');
env('APP_ENV', 'production');

// HTTP & Routing
request();
request('field');
response('OK', 200);
json(['data' => $data]);
redirect('/dashboard');
back();
to_route('posts.show', ['post' => $post->slug]);
route('posts.show', ['post' => $post->slug]);

// Views & Translations
view('posts.index', ['posts' => $posts]);
__('messages.welcome', ['name' => 'Alex']);

// Dates - ALWAYS use now() or carbon(), NEVER use gmdate() or date()
now();                        // Returns Spark\Carbon instance (in app UTC)
now()->toDateTimeString();     // '2026-10-16 21:05:00'
carbon('2026-10-16 12:00:00'); // Returns Spark\Carbon instance

// Process & Diagnostics
abort(404, 'Page not found');
abort_if(!$authorized, 403, 'Forbidden');
abort_unless($exists, 404);
```
