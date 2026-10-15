# Global Helpers and Facades

Read this reference for global helpers, facades, and utility functions. Source paths and command working directories refer to the application root.

---

## Facades

TinyCore provides first-class static facades under `Spark\Facades\*`. They provide clean, testable, static access to underlying services in the application container:

| Facade | Service / Class | Primary Use Case |
| --- | --- | --- |
| `Spark\Facades\App` | `Spark\Foundation\Application` | Service container binding, resolving, and environment checks. |
| `Spark\Facades\Auth` | `Spark\Http\Auth` | User authentication, guards, credentials verification, tokens. |
| `Spark\Facades\Blade` | `Spark\View\Blade` | Template compilation, directive registration, component rendering. |
| `Spark\Facades\Cache` | `Spark\Cache\Cache` | Storing, retrieving, atomic locks, and cache invalidation. |
| `Spark\Facades\DB` | `Spark\Database\DB` | Database transactions, connection switching, raw queries, table queries. |
| `Spark\Facades\Event` | `Spark\Events` | Event dispatching and listener registration. |
| `Spark\Facades\Gate` | `Spark\Http\Gate` | User authorization policies and ability checks. |
| `Spark\Facades\Hash` | `Spark\Hash` | Password hashing (Bcrypt/Argon2) and verification. |
| `Spark\Facades\Http` | `Spark\Http\Client\Http` | Fluent outbound HTTP client with JSON handling and authentication. |
| `Spark\Facades\Lock` | `Spark\Cache\Lock` | Atomic distributed locking. |
| `Spark\Facades\Log` | `Spark\Log` | Application logging (`info`, `error`, `warning`, `debug`). |
| `Spark\Facades\Mail` | `Spark\Utils\Mail` | Fluent email sending with Blade view rendering. |
| `Spark\Facades\Route` | `Spark\Http\Routing\Router` | Defining routes, route groups, resource routes, middleware. |
| `Spark\Facades\Storage` | `Spark\Storage\Storage` | Filesystem storage (local, public, S3-compatible). |

### Facade Examples

```php
use Spark\Facades\Auth;
use Spark\Facades\Cache;
use Spark\Facades\DB;
use Spark\Facades\Gate;
use Spark\Facades\Http;
use Spark\Facades\Mail;
use Spark\Facades\Storage;

// Transactions
DB::transaction(function () {
    // atomic writes
});

// Cache with auto-compute
$stats = Cache::remember('site_stats', fn () => calculateStats(), '+30 minutes');

// Outbound HTTP
$response = Http::withToken($token)->get('https://api.example.com/me');

// Mail
Mail::to($user->email)->subject('Hello')->view('emails.hello')->send();

// Storage
Storage::disk('public')->put('documents/file.pdf', $fileContents);

// Authorization
Gate::authorize('update-post', $post);

// Authentication
if (Auth::attempt(['email' => $email, 'password' => $password])) {
    $user = Auth::user();
}
```

---

## Modern Support Helpers

Spark includes a rich suite of modern Laravel-inspired support utilities:

### 1. Collections (`collect`)

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
$user = tap(User::create($data), function (User $user) {
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
route('posts.show', ['post' => 5]);

// Views & Translations
view('posts.index', ['posts' => $posts]);
__('messages.welcome', ['name' => 'Alex']);

// Dates
now();              // Returns Spark\Carbon instance
carbon('2026-10-15');

// Process & Diagnostics
abort(404, 'Page not found');
abort_if(!$authorized, 403, 'Forbidden');
abort_unless($exists, 404);
```
