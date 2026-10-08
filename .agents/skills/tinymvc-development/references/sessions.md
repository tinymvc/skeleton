# Sessions and cookies

Read for browser state, login/logout, cookies, and handler changes. Inspect `Http/Session.php`, `Http/Session/`, and the application’s `config/session.php` in the installed package.

## Configure session storage

Spark 4.0 adds `config/session.php` with `handler` set to `database`, `file`, or `redis`. The skeleton defaults to `database` (`SESSION_HANDLER`); run the framework migration before serving web requests.

| Handler | Connection settings | Storage |
| --- | --- | --- |
| `database` | `connection`, `table` (default `sessions`) | `id`, base64 `payload`, `last_activity` |
| `file` | `path` | `storage/framework/sessions` |
| `redis` | Shared Redis connector options | Application-prefixed keys with TTL |

`lifetime` is idle lifetime in **minutes**, default 120. Every write refreshes it. Expired data is rejected on read; file/database garbage collection removes old records, while Redis TTL handles expiry. `expire_on_close` controls browser cookie lifetime without disabling server expiry.

`cookie_name` defaults to `spark_session`. `cookie_settings` accepts `path`, `domain`, `secure`, `http_only`, and `same_site`. A null `secure` detects direct HTTPS; when TLS terminates at a proxy, explicitly set `SESSION_COOKIE_SECURE=true` in production. Strict session-ID validation rejects unknown client-supplied IDs. Configure `gc_probability` and `gc_divisor` for PHP's garbage-collection lottery.

The file handler holds an exclusive lock from read until close, serializing requests for the same session ID. Database and Redis handlers use atomic writes but do not hold a request-long session lock: concurrent requests can overwrite changes. Close sessions promptly, or explicitly serialize application operations that must update session state together. File sessions require a local filesystem.

## Read and write state

The Session service starts a PHP session during normal web use. `session()` returns the service; an array sets values and a string reads one value:

```php
session(['cart_count' => 3]);
$count = session('cart_count', 0);
session()->set('locale', 'en');
```

The second argument of `session('key', $default)` is a **read default**, not a value to write. Use `session(['key' => $value])` or `set()` / `put()` for writes.

## Common operations

| Method | Action |
| --- | --- |
| `get($key, $default)` | Read a value |
| `set($key, $value)`, `put($key, $value)` | Store a value |
| `put($array)` | Store several values |
| `has($key)` | Check a non-null value exists |
| `delete($key)`, `forget($keys)` | Remove values |
| `pull($key, $default)` | Read and remove a value |
| `all()` | Read session data |
| `flush()` | Clear data |
| `id()` | Current session ID |
| `close()` | Finish writing the PHP session |

The methods are also available on `Spark\Http\Session`. Normal CLI execution does not start browser sessions; the testing environment supplies isolated session behavior.

## Flash messages

```php
session()->flash('success', 'Your profile was saved.');
return redirect('/profile');
```

Read with `getFlash('success')`; `hasFlash()` checks availability and `clearFlash()` removes flash state. Reading `getFlash()` removes the value immediately. Unread values remain until retrieved or cleared; this implementation does not automatically age every flash key out after one request. Read a message once and pass it to the view if several components need it.

Response helpers `with()`, `withErrors()`, and `withInput()` integrate with this state. Keep old input limited to safe fields.

## Regeneration and logout

`regenerate($deleteOldSession)` changes the session ID. Regenerate after a successful login or privilege change to avoid carrying an old session identifier into the authenticated state. `invalidate()` clears state and regenerates; `destroy()` ends the session.

Use the Auth service's `logout()` before invalidating when ending a login, so remembered-authentication state is handled as well.

## Cookies

```php
$preference = cookie('display_mode');
cookie('display_mode', 'compact', 60 * 60 * 24 * 30);
```

A null value reads the cookie. An integer third argument is a relative lifetime in seconds; zero creates a session cookie and a negative value expires it. An options array follows PHP's `setcookie` shape:

```php
cookie('display_mode', 'compact', [
    'expires' => time() + 86400,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
```

With an options array, `expires` is an absolute Unix timestamp. The helper defaults to HttpOnly and SameSite Lax, but `secure` defaults to false; set the intended HTTPS behavior explicitly. Cookies are client input and should not be treated as trusted authorization data.

## Operational notes

Send session/cookie changes before output reaches the browser. Configure PHP session storage and cookie settings for your deployment; multiple servers need compatible shared session storage or a deliberate routing policy. See [CSRF & CORS](middleware.md) for session-backed request protection.
