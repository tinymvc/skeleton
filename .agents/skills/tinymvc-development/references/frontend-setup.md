# Frontend setup: Blade, Vite, FireLine, and Inertia

Read when installing an explicitly requested frontend integration, creating its entry/layout, or diagnosing asset and response-protocol mismatches. For ordinary template edits, use [Views and frontend](views-frontend.md). Preserve the application's current stack unless the task asks to change it.

## Establish versions and ownership

Inspect `composer.json` and installed/locked packages, `package.json` and its lockfile, `bootstrap/providers.php`, `bootstrap/app.php`, `vite.config.*`, `resources/app`, and the root view. Confirm who owns the page DOM and navigation before adding another client. Do not let FireLine and Inertia intercept the same links/forms or patch the same mount.

| Stack | Initial response | Client visit/write contract |
| --- | --- | --- |
| Blade + Alpine | Full Blade HTML | Native navigation/forms or explicitly implemented AJAX |
| FireLine v2 + Alpine | Full Blade layout with fragment target | JSON render/navigation/form envelope from `tinymvc/fireline-php` |
| Inertia + React/Vue | Adapter root view and mount payload | Inertia page/redirect protocol from `tinymvc/inertia-php` |

Optional adapters register their own route macros/directives. A macro name alone does not install a package. Verify their Composer constraints against installed TinyCore; do not upgrade the framework just to match an example without that task scope.

## Blade and Vite baseline

The skeleton's entry is `resources/app/app.js`, importing `app.css` and starting Alpine once. Its Vite config uses root `resources/app`, alias `@` for that directory, port 5173 with strict port, production base `/build/`, output `public/build`, and manifest `.vite/manifest.json`. It registers Tailwind and Blade full-reload plugins.

Use `@vite('app.js')` in the initial layout. Arrays such as `@vite(['app.tsx', 'app.css'])` require corresponding build entries; CSS imported by the JS entry need not be a separate entry. Keep PHP helper paths, Vite inputs, and output paths consistent. Do not load the same bundle from both a layout and its page fragments.

For development, run `npm run dev` and the PHP app (`php spark serve`) separately, then browse the PHP URL. Build with `npm run build`; deploy the complete `public/build` including hidden `.vite/manifest.json`. Inspect `Spark\Utils\Vite` for custom `root`, `entry`, `dist_path`, `manifest_path`, `running`, host, and port settings. Explicit `running: false` selects production assets; do not leave a forced development URL in production.

For plain Blade forms, retain `@csrf`, `@method`, normal validation/old-input handling, and escaped output. See [requests](requests-validation.md) and [views](views-frontend.md) for exact APIs.

## FireLine v2 setup

The locally documented v2.1 client requires Alpine 3.15+ in 3.x. The PHP adapter requires PHP 8.2+ and TinyCore `^4.0.7`. Confirm the installed release before using advanced APIs.

```bash
composer require tinymvc/fireline-php
npm install fireline@^2.1 alpinejs@^3.15
```

Add `Spark\Fire\FireServiceProvider::class` to the existing provider list. Put `Spark\Fire\FireMiddleware::class` before downstream handlers in `withMiddleware(queue: [...])`, preserving `'csrf'` and other middleware. The middleware handles downstream validation exceptions and response headers; it does not convert arbitrary HTML into fragments.

In the existing Vite entry, import `FireLine from 'fireline'`, call `Alpine.plugin(FireLine)`, configure `window.FireLine.settings`, then call the existing single `Alpine.start()`. Keep CSS and other imports. The default target is `#app > div`.

The provider adds GET-only `Route::fire('/about', 'pages/about', $props)`. Controller equivalents are `Spark\Fire\Fire::render($template, $props, title: $title)` and `fire($template, $props)`. These return full HTML for ordinary requests and `{html, title}` JSON for `X-FireLine: 1` requests.

Use a conditional layout; the adapter does not extract fragments:

```blade
@if (!is_fire_js())
    @extends('layouts/app')
@endif

@section('title')About@endsection
@section('content')
<div>
    <h1>{{ $message }}</h1>
    <a x-navigate href="/">Home</a>
</div>
@endsection

@if (is_fire_js())
    @yield('content')
@endif
```

The full layout contains `<div id="app">@yield('content')</div>` and its `@vite` outside that target. Page fragments replace the target itself and need exactly one element root still matching the selector. Do not return a full HTML document to an intercepted visit.

### Forms and response API

```blade
<form x-data="{ form: $form() }" x-form action="/profile" method="post">
    @csrf
    @method('PATCH')
    <input name="name">
    <p x-text="form.firstError('name')"></p>
    <p x-text="form.message"></p>
    <button :disabled="form.processing">Save</button>
</form>
```

| API | Contract |
| --- | --- |
| `fire()->success($message, $data = [])` | JSON success; resets native controls to defaults |
| `fire()->error($message, $status = 400)` | JSON error; retains input |
| `fire()->handleValidation($message, $errors, $status = 422)` | Error arrays keyed by field; 422 activates field validation |
| `fire()->navigate($url)` | FireLine visit; native redirect for ordinary requests |
| `fire()->redirect($url)` | Full browser navigation; native redirect for ordinary requests |
| `is_fire_js()`, `is_fire_preload()`, `is_fire_partial()` | Representation flags, never authorization |

`x-form` finds `form` in Alpine scope; `$form()` supplies processing/errors/status/message and `firstError`, `hasError`, `reset`, `submit`. Requests serialize named controls, not arbitrary state properties. `reset()` clears feedback only. Extra success data stays in `envelope.raw.data`. Legacy `x-submit` is supported for basic message feedback.

Normal `Request::validate()` failures are converted by FireMiddleware when inside its scope. Preserve CSRF and ownership checks. The adapter's success/error/validation helpers always return JSON; build a native post/redirect/get branch if the same form must work without JavaScript. Avoid plain HTML and empty/204 responses for FireLine operations.

### Advanced client behavior

- `x-preload` / `x-preload.hover` require safe GETs and the same required markup/data as navigation. `is_fire_preload()` can skip optional analytics, not required queries. Preload cache defaults to 30 seconds/50 entries and writes invalidate it.
- `$partial(url)` with `x-partial="state"` retains its host and reconciles children. Return a JSON render envelope with child markup (multiple roots/empty allowed), not a full layout or raw HTML. Use `load()`, `loadMore(url)`, `startInterval(ms)`, `stopInterval()`. Keep controls outside the host; do not combine it with competing `x-html`/`x-text`/`x-ignore`.
- `x-poll` and partial intervals use milliseconds; global `timeout` uses seconds. Polling pauses while hidden/offline. Host removal cleans up partial requests/intervals.
- `@click` and `:class` work in v2. Stable server `key` attributes preserve matching nodes; changed keys/directives intentionally reset state. `x-model` is client-owned.
- `executeScripts` defaults to true; keep shared bundles outside fragments. Disabling script execution is not HTML sanitization.
- `$fire.navigate`, `reload`, and `formSubmit` are asynchronous. `fireEnd` includes errors/cancellation; use `fireNavigate` or inspect the returned envelope for success. Preloads and partials do not emit global request events.
- Keep initial `FireLine.settings.assetVersion` equal to the version sent through `fire()->version($buildId)` each request. The adapter does not derive it automatically from the Vite manifest. Preserve its `Vary` and no-cache response headers.

The adapter replaces removed core APIs with `fire()` / `is_fire_js()` / `Route::fire()`. Do not recreate the old `fireline()` helper or `X-Fireline-Agent` protocol. For complete version-specific options, inspect the installed JS README and `vendor/tinymvc/fireline-php/README.md`, `src/FireService.php`, `src/FireMiddleware.php`, and `src/FireServiceProvider.php`.

## Inertia setup and API boundaries

Install `tinymvc/inertia-php` only for an Inertia task. Read its installed README and Composer requirements before selecting compatible `@inertiajs/react` or `@inertiajs/vue3`, framework dependencies, and Vite plugin. Do not infer the Inertia major from TinyCore's version or copy a React entry into a Vue app.

Register `Inertia\InertiaServiceProvider::class` alongside existing providers. It registers `inertia()`, `@inertia`, and `Route::inertia()`. The root Blade view includes the selected `@vite` entry and `@inertia`. Configure the adapter's root view and mount ID consistently with `createInertiaApp()`; inspect the installed adapter's mount payload format rather than hand-building `data-page` or JSON scripts.

Configure the React/Vue Vite plugin and a matching `.tsx`/`.js` entry. Use a bounded page resolver such as `import.meta.glob('./pages/**/*.tsx')` for a React app, matching the actual folder/case/exports. Confirm the client's installed `createInertiaApp` signature, initial mount and optional progress setup. Keep React refresh configuration aligned with the PHP Vite helper. Use an existing working entry in the target app as the primary example.

```php
use Inertia\Facades\Inertia;

return Inertia::render('Posts/Index', [
    'posts' => Post::orderDesc('id')->paginate(20),
]);
```

Here `Post` is an imported application model. Component names must resolve in the client entry. The helper form is `inertia('Posts/Index', $props)`; static pages can use the registered `Route::inertia()` macro. Props are client-visible: select explicit public fields, authorize reads, and retain tenant constraints inside deferred queries.

Inspect installed support/signatures for `share`, closures, `optional`, `defer`, `always`, `merge`, `deepMerge`, `once`, `shareOnce`, and `scroll` before using them. They are version-specific; `lazy()` is not interchangeable with v3 `optional()`. Avoid eagerly evaluating an expensive query before wrapping its result in a deferred prop.

For form writes, validate/authorize server-side and return the adapter's redirect to a GET page. Preserve session/CSRF and `X-Inertia` headers so validation follows the adapter's redirect/error flow. Do not send FireLine envelopes or a plain JSON success response to an Inertia visit. Verify `redirect`, `back`, `location`, flash, and asset-version behavior against the installed adapter; do not guess Laravel APIs. SSR requires explicit package support, not just a Vite server bundle.

## Verification and diagnosis

Build the chosen assets and run relevant application tests. Exercise a direct full-page load, a client visit, a form success, invalid input, expired session, CSRF rejection, and production manifest loading. For FireLine, also check single-root replacement, back/forward, partial teardown, preloading, and asset mismatch; for Inertia, check page resolution, partial props, redirects/error state, and asset-version conflicts.

Inspect the actual request headers, response status/content type/body, and target DOM when navigation fails. Raw HTML returned to FireLine usually means a route used `view()` instead of the adapter, the provider/middleware is missing, or the request header was lost. A missing Inertia component often means resolver path/case or build entry mismatch. A Vite manifest failure means the build is missing or PHP/JS paths differ. Do not catch these failures and silently display success.
