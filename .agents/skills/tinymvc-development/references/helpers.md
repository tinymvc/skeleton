# Helpers

Read this reference for global helpers, facades. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

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
view('emails.welcome', ['user' => $user]);

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


## Utility selection

Use native `Arr`, `Str`, collections and date helpers when they express the requirement clearly. Inspect installed signatures and optional Composer dependencies before using Markdown, transliteration, UUID or image features. A helper existing in Laravel does not establish its presence in Spark.

Localization uses `app.locale`, `app.locale_dir` and `locale_dir()`; preserve the app's language files and fallback conventions. Use the configured timezone and explicit parsing/formatting at API boundaries; test ambiguous or invalid dates when they affect behavior.

Use the installed password hashing APIs for passwords and the framework's encryption/signing APIs for reversible or signed values. Preserve application keys across releases and handle invalid/tampered payloads as failures. Do not substitute general digests for password hashing or log decrypted secrets. Check `Hash.php` and the app's configured algorithms before changing formats.

Support helpers are Laravel-derived, but the surrounding HTTP, ORM and service contracts are Spark-specific. Do not modify `src/Support` merely to implement an application feature.
