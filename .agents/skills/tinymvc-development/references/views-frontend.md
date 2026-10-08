# Views Frontend

Contents:

- [Render a view](#render-a-view)
- [Escaped and raw output](#escaped-and-raw-output)
- [Layouts and sections](#layouts-and-sections)
- [Includes](#includes)
- [Components and slots](#components-and-slots)
- [Attribute helpers](#attribute-helpers)
- [Control flow](#control-flow)
- [Forms, sessions, and permissions](#forms-sessions-and-permissions)
- [Shared data and composers](#shared-data-and-composers)
- [Custom directives and template paths](#custom-directives-and-template-paths)
- [Compiled templates](#compiled-templates)
- [Empty lists and compiler boundaries](#empty-lists-and-compiler-boundaries)
- [Frontend Integrations](#frontend-integrations)

Read this reference for views, frontend integrations. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Render a view

Templates live under `resources/views` by default, configured by `app.views_dir`. Dot notation maps to directories:

```php
return view('posts.index', ['posts' => $posts]);
```

This renders `resources/views/posts/index.blade.php` into a response. `blade()->render('posts.index', $data)` renders a string. Generate files with `php spark make:view posts.index` and components with `php spark make:component alert`.

## Escaped and raw output

```html
<h1>{{ $title }}</h1>
{!! $trustedHtml !!}
{{-- This comment is removed during compilation. --}}
```

Use escaped `{{ }}` for user data. Raw output is for trusted HTML. `@json($value)` uses the framework's JavaScript encoder when embedding data in scripts. `@verbatim ... @endverbatim` preserves text that would otherwise be compiled.

## Layouts and sections

Create `layouts/app.blade.php`:

```html
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'My application')</title>
    @vite('app.js')
</head>
<body>
    @include('partials.navigation')
    <main>@yield('content')</main>
</body>
</html>
```

A page extends it:

```html
@extends('layouts.app')
@section('title', 'Posts')

@section('content')
    <h1>Posts</h1>
    @foreach($posts as $post)
        <article><h2>{{ $post->title }}</h2></article>
    @endforeach
@endsection
```

`@stop` aliases `@endsection`; `@show` ends and immediately yields a section. `@hasSection()` and `@sectionMissing()` check whether a section exists. `@yield()` can provide a default.

## Includes

`@include('partials.header', ['title' => $title])` renders a partial with data. Use `@includeIf()` for an optional template, `@includeWhen($condition, 'view', $data)` for a conditional include, and `@includeUnless()` for the inverse.

Keep partials focused on presentation. Fetch shared data in a composer or provider instead of querying a database in a loop inside a template.

## Components and slots

Create `components/notice.blade.php`:

```html
@props(['tone' => 'info'])
<div {{ $attributes->merge(['class' => 'notice']) }}>
    <strong>{{ $tone }}</strong>
    {!! $slot !!}
</div>
```

Use it in a page:

```html
<x-notice tone="success" class="mt-4">
    <p>Your changes have been saved.</p>
</x-notice>
```

`<x-name />` creates a self-closing component. Prefix an attribute with `:` to evaluate PHP, as in `:message="$message"`; shorthand `:$user` passes a same-named variable. Slot content is rendered HTML. Dynamic slot closures currently do not capture the parent template’s local variables. Pass dynamic data as component props and render it inside the component, rather than reading parent-local variables inside a slot. Component attributes are represented by `Spark\View\Attributes`.

## Attribute helpers

`$attributes->get()`, `has()`, `hasAny()`, `only()`, `except()`, `filter()`, and prefix filters select attributes. `merge()`, `class()`, and `style()` combine defaults and conditional values. `props()` extracts component props; `toHtml()` serializes attributes.

Template directives also support `@class()`, `@style()`, `@attributes()`, and boolean attributes `@checked()`, `@selected()`, `@disabled()`, `@readonly()`, and `@required()`.

```html
<input type="checkbox" name="published" @checked($post->published)>
```

## Control flow

| Purpose | Directives |
| --- | --- |
| Conditions | `@if`, `@elseif`, `@else`, `@endif` |
| Inverse condition | `@unless`, `@endunless` |
| Presence | `@isset`, `@endisset`, `@empty`, `@endempty` |
| Loops | `@foreach` / `@endforeach`, `@for` / `@endfor`, `@while` / `@endwhile` |
| Loop control | `@continue`, `@break` |
| Branching | `@switch`, `@case`, `@default`, `@endswitch` |
| PHP | `@php` / `@endphp`, `@use('Namespace\\Class')` |

This is TinyCore's own compiler. Do not assume directives from another Blade implementation are supported unless registered by an integration.

## Forms, sessions, and permissions

| Purpose | Directives |
| --- | --- |
| Form protection | `@csrf`, `@method('PATCH')` |
| Old input | `@old('name')` |
| Validation | `@error('email')` / `@enderror`, `@errors('email')` / `@enderrors` |
| Authentication | `@auth` / `@endauth`, `@guest` / `@endguest` |
| Authorization | `@can(...)` / `@endcan`, `@cannot(...)` / `@endcannot`, `@authorize(...)` |
| Session presence | `@session('message')` / `@endsession` |
| Debugging | `@dump(...)`, `@dd(...)`, `@abort(...)` |

Validation blocks expose `$message`. Permission-based UI is a convenience: enforce the same rule in the controller or middleware. See [Authorization](auth-gate.md) for argument handling.

Authentication directives also accept a registered guard name:

```blade
@auth('admin')
    <p>Signed in as {{ user('email', '', guard: 'admin') }}</p>
@endauth

@guest('admin')
    <a href="{{ route('admin.login') }}">Admin sign in</a>
@endguest
```

Without an argument, `@auth` and `@guest` use the default guard. These directives call `is_logged($guard)` and `is_guest($guard)`; they do not switch the identity used by an unqualified `user()` call inside the block. Register guards as shown in [Authentication](auth-gate.md) and enforce access on the route too.

## Shared data and composers

Register shared values and composers in a provider's `boot()` method:

```php
use Spark\Facades\Blade;

Blade::share('siteName', config('app.name'));
Blade::composer('partials.navigation', function ($view) {
    $view->share('currentUser', user());
});
```

Templates also have application context such as `$app`, `$request`, `$session`, and `$errors`. `@share()` shares a value from a template. Prefer explicit page data when global state would make templates difficult to reason about.

## Custom directives and template paths

```php
blade()->directive('year', function ($expression) {
    return '<?php echo date("Y"); ?>';
});
```

A directive callback returns generated PHP; it does not render a request value immediately. Treat directive definitions as application code. Additional view locations can be registered through `setUsePath()` / `usePath()`; `templateExists()` checks resolution.

## Compiled templates

The engine compiles templates to its cache directory. Clear compiled views after changing directives or troubleshooting stale markup:

```bash
php spark view:clear
```

For asset loading, use the application’s Vite configuration and `@vite` entry points. For dynamic navigation over Blade responses, see [FireLine](https://tinymvc.github.io/fireline).

## Empty lists and compiler boundaries

Use an explicit empty-state condition around a normal loop:

```html
@if(empty($posts))
    <p>No posts have been published yet.</p>
@else
    @foreach($posts as $post)
        <h2>{{ $post->title }}</h2>
    @endforeach
@endif
```

This example expects an array; for a Collection use `$posts->isEmpty()`. The current compiler does not provide `@forelse`, `$loop` metadata, `@push` / `@stack`, or Laravel class-based/named-slot component behavior. Use the documented sections, includes, default component slot, and explicitly passed data instead of assuming unsupported syntax will compile.

Component prop values and HTML attributes have different jobs. Extract declared props with `@props`, keep additional HTML attributes in `$attributes`, and render untrusted text through escaped output inside the component. Clear compiled views after changing a custom directive because an unchanged template file may otherwise keep its earlier compiled code.

## Frontend Integrations

Inspect `package.json`, `vite.config.js`, existing templates/pages, and registered providers before choosing an approach. The skeleton uses Vite, Tailwind, Alpine, and Blade; optional packages may add different capabilities.

| Existing stack | Development approach |
| --- | --- |
| Blade + Alpine | Server-rendered views with targeted browser interactions; preserve CSRF form handling |
| FireLine | Follow the installed integration's navigation/form conventions and current app templates |
| Inertia PHP + React/Vue | Verify the adapter/provider and matching client version, then return the app's existing Inertia responses |
| Orbit | Extend its existing administration resources, BREAD, access rules, and React/shadcn components rather than creating a parallel admin architecture |

`Route::inertia()` needs the adapter service provider; a route method name alone does not install the integration. Inspect the installed package README/source for version-sensitive props and APIs. Do not add FireLine, Inertia, React, Vue, or Orbit unless the app/task calls for that integration. Use the app's Vite asset helper and build configuration instead of hardcoded development-server URLs.

