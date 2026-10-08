# Auth Gate

Read this reference for auth and authorization. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

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

Named guards are separate Auth singletons. In 4.0, `config/auth.php` defines the default `guard`, shared `model`, and `guards` map; configured named guards resolve lazily. The skeleton API guard uses JWT only. Alternatively, register a guard in a provider before use:

```php
\Spark\Http\Auth::register(
    model: \App\Models\Admin::class, // Application-defined model and table
    config: [
        'channels' => ['session'],
        'session_key' => 'admin_id',
        'cookie_name' => 'admin_auth',
        'cache_name' => 'admin_auth_cache',
        'login_route' => 'admin.login',
        'redirect_route' => 'admin.dashboard',
    ],
    guard: 'admin',
);

$adminAuth = \Spark\Http\Auth::guard('admin'); // Also auth('admin')
$admin = user(guard: 'admin');
$email = user('email', 'Guest', guard: 'admin');
$signedIn = is_logged('admin'); // Also $request->isAuthenticated('admin')
$guest = is_guest('admin');    // Also $request->isNotAuthenticated('admin')
$admin = $request->user(guard: 'admin');
```

`Auth::register()` registers `auth.<guard>`; it does not register a user account. `default` is reserved and resolves `Spark\Http\Auth`, so customize that class binding rather than registering a guard named `default`. Use distinct session keys, cookie names, and cache names for independent guards. Selection does not change subsequent default `auth()` / `user()` calls. Blade supports `@auth('admin')` / `@guest('admin')`; use the same guard when reading the identity inside those blocks and in gate callbacks.

Channels are checked in fixed order JWT, Basic, then session. Basic looks up `username` or `email`; both columns must exist when using that channel. JWTs identify the model, not the guard name; two guards using the same model/key are not separate token audiences. Regenerate the session after successful browser login. Guard logout clears its configured identity/cookie; session invalidation clears all guards sharing that session.

JWT defaults are `jwt_expire: '3 months'` and `jwt_token_table: null`. The removed
`validate_jwt_hash` option is not needed: stateless tokens require a SHA-256 `jti`
fingerprint of the user's numeric ID, email, and stored password. Old MD5-based
tokens need to be reissued. Keep payload overrides application-controlled.
`makeToken($user, $payload = [])` only signs; `createToken(?Model $user = null, array $payload = [])`
uses the explicit user or falls back to the guard's current user. A guest can issue
a token for an explicitly supplied, authorized user; issuance does not log in or
switch the current identity. Omitting the user as a guest throws. Pass payloads as
the second argument or use `createToken(payload: [...])`, not a positional array
as the first argument. It additionally registers a row when a token table is
configured. In that mode each token gets an independent random `jti`, and `exp`
overrides also determine the stored expiry.

For a configured `jwt_token_table`, create columns `user_id`, `token_hash` (unique
string, 64 characters for generated IDs), `expire_at`, and `created_at`, with an
optional primary `id` and the appropriate user foreign key. Use a separate table
for different user models. `tokens()` returns the current user's stored rows;
`revokeToken($tokenHash)` is scoped to that user. Both require the table and an
authenticated user. `token()` returns the validated header's `jti`, not proof that
the user/token row exists. Table-backed logout revokes the selected bearer token;
stateless logout cannot revoke issued JWTs. Password changes require explicit
revocation of table-backed tokens when the application wants that behavior.

Auth validates signature, numeric subject, integer issue/expiry times, model
provider, non-empty `jti`, and issuer origin (scheme/host/port). JWT-only guards
should use `channels: ['jwt']`. `JWT::decode()` alone verifies the signature but
does not apply these Auth checks. Registered token rows must exist and be unexpired;
revocations apply to fresh request authentication, not an already-loaded identity.

`Spark\Foundation\Http\Middlewares\AuthMiddleware` is namespaced and accepts any matching guard, including negated names such as `auth:!admin`. Extend it and override `failed(Request $request, array $guards)` for custom failure responses; the default aborts with 401. Register its alias in `bootstrap/middlewares.php`. Guard matching does not switch the default identity for downstream code.

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

Current JWT methods are `makeToken($user, $payload)` (sign only) and `createToken(?Model $user = null, array $payload = [])` (explicit or current user, register when configured), replacing `getJwtToken()` / `createJwtToken()`. `tokens()` lists the user's registered tokens; `token()` exposes a verified bearer jti but does not replace authentication. `revokeToken($hash = null)` defaults to the current bearer identifier and remains owner-scoped.

