# Views and Frontend (Blade)

Read this reference for Blade templates, components, view helpers, and frontend integrations. All paths are relative to the application root.

---

## 1. Modern Component-First UI (`<x-component>`)

> [!IMPORTANT]
> **Component-First Standard**:
> Always use Blade components (`<x-component ...>`) for reusable UI elements (alerts, buttons, cards, modals, navigation, form inputs).
> **Never use `@include('partials.alert')` for UI components.** Reserve `@include` strictly for non-componentized document sections or large email template pieces.

### Defining Components

Component templates live in `resources/views/components/`. Use `@props` for declared data and `$attributes` for remaining HTML attributes:

```blade
{{-- resources/views/components/alert.blade.php --}}
@props([
    'type' => 'info',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'alert alert-' . $type]) }} role="alert">
    {{ $message ?? $slot }}
</div>
```

```blade
{{-- resources/views/components/button.blade.php --}}
@props(['type' => 'button', 'variant' => 'primary'])

<button type="{{ $type }}" {{ $attributes->class(['btn', 'btn-' . $variant => $variant]) }}>
    {{ $slot }}
</button>
```

```blade
{{-- resources/views/components/forms/input.blade.php --}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium mb-1">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'form-input w-full rounded border-gray-300']) }}
    >
    @error($name)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
```

### Using Components in Views

TinyCore supports clean, expressive component syntax:

```blade
{{-- 1. Variable prop shorthand (:$message is identical to :message="$message") --}}
<x-alert :$message type="success" />

{{-- 2. Explicit bound expression (:message) for custom variables or expressions --}}
<x-alert :message="$errorMessage" type="warning" class="mb-4" />

{{-- 3. Block component with slot content ($slot) --}}
<x-alert type="danger">
    <strong>Error!</strong> Could not connect to payment gateway.
</x-alert>

{{-- 4. Nested components via dot notation (components/forms/input.blade.php) --}}
<x-forms.input name="email" label="Email Address" type="email" required />

{{-- 5. Action buttons --}}
<x-button type="submit" variant="primary">Save Changes</x-button>
```

### Component Attributes (`Spark\View\Attributes`)

Inside component templates, `$attributes` provides powerful fluent helpers:
* `$attributes->merge(['class' => 'default-class'])`: Merges defaults with consumer-provided attributes without clobbering.
* `$attributes->class(['base', 'active' => $isActive])`: Conditionally compiles class strings.
* `$attributes->has('id')`: Checks if an attribute exists.
* `$attributes->get('name', 'default')`: Reads attribute value.
* `$attributes->except('class')`: Excludes attributes.

---

## 2. Helpers vs. Facades in Blade Views

> [!CAUTION]
> **Strict Rule: Never Use Static Facades in Blade Views!**
> Do **NOT** use `\Spark\Facades\*` (e.g. `\Spark\Facades\Session`, `\Spark\Facades\Auth`, `\Spark\Facades\App`) inside Blade templates.
> In views, **always use native Spark global helpers and directives**.

| Purpose | Non-Smart / Banned in Blade ❌ | Smart & Idiomatic in Blade ✅ |
| --- | --- | --- |
| **Session Flash** | `\Spark\Facades\Session::get('status')` | `session('status')` or `@session('status') {{ $value }} @endsession` |
| **Session Check** | `\Spark\Facades\Session::has('error')` | `session()->has('error')` |
| **Auth User** | `\Spark\Facades\Auth::user()` | `auth()->user()` or `user()` or `user('email')` |
| **Auth Check** | `\Spark\Facades\Auth::check()` | `@auth ... @endauth` or `@guest ... @endguest` |
| **App / Services** | `\Spark\Facades\App::make('cache')` | `app('cache')` or `app(SomeService::class)` |
| **Config Value** | `\Spark\Facades\Config::get('app.name')` | `config('app.name')` |
| **Current Request** | `\Spark\Facades\Request::input('q')` | `request('q')` |
| **Route URL** | `\Spark\Facades\Route::url(...)` | `route('posts.show', $post->slug)` |
| **Current Time** | `\Spark\Facades\Date::now()` or `date()` | `now()->year` or `today()->toDateString()` |
| **CSRF Token** | `\Spark\Facades\Session::token()` | `@csrf` or `csrf_token()` or `csrf_field()` |
| **Authorization** | `\Spark\Facades\Gate::allows(...)` | `@can('update-post', $post) ... @endcan` or `@cannot` |

### Blade Helper Example

```blade
@auth
    <span>Welcome back, {{ user('name') }}!</span>
@endauth

@session('status')
    <x-alert :message="session('status')" type="success" />
@endsession

<form method="POST" action="{{ route('posts.store') }}">
    @csrf
    <x-forms.input name="title" :value="old('title')" placeholder="Post title" />
    @error('title')
        <p class="text-red-500">{{ $message }}</p>
    @enderror

    <x-button type="submit">Publish</x-button>
</form>
```

---

## 3. Layouts: Components vs. Sections

### Component-Based Layouts (Recommended)

Create `resources/views/components/layout.blade.php`:

```blade
@props(['title' => config('app.name')])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 text-gray-900">
    <x-navigation />
    <main class="container mx-auto px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>
```

Use in a page view:

```blade
<x-layout title="All Posts">
    <h1 class="text-2xl font-bold mb-4">Blog Posts</h1>
    <div class="grid gap-4">
        @foreach($posts as $post)
            <x-card :title="$post->title">
                <p>{{ $post->excerpt }}</p>
            </x-card>
        @endforeach
    </div>
</x-layout>
```

### Classic Section Layouts (`@extends`)

```blade
{{-- layouts/app.blade.php --}}
<!doctype html>
<html>
<head>
    <title>@yield('title', config('app.name'))</title>
</head>
<body>
    <x-navigation />
    <main>@yield('content')</main>
</body>
</html>

{{-- posts/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Posts')

@section('content')
    <h1>Posts</h1>
@endsection
```

---

## 4. When to Use `@include`

Reserve `@include()` **only** for:
- Large static document sections (e.g. `@include('legal.terms-of-service-body')`).
- Multi-section email templates (e.g. `@include('emails.partials.footer')`).
- Never use `@include` for reusable interactive widgets; use `<x-component>` instead.

---

## 5. Directives and Control Flow Reference

| Directive | Purpose |
| --- | --- |
| `@if`, `@elseif`, `@else`, `@endif` | Conditional blocks |
| `@unless`, `@endunless` | Inverse condition |
| `@isset`, `@empty` | Presence / emptiness checks |
| `@foreach`, `@for`, `@while` | Loops |
| `@class([...])` | Conditional CSS class string |
| `@checked($bool)`, `@selected($bool)` | HTML input state attributes |
| `@disabled($bool)`, `@readonly($bool)`, `@required($bool)` | Form input attributes |
| `@csrf`, `@method('PUT')` | Form security & HTTP spoofing |
| `@error('field') ... @enderror` | Validation error block exposing `$message` |
| `@session('key') ... @endsession` | Session presence block |
| `@auth($guard)`, `@guest($guard)` | Authentication state blocks |
| `@can('ability', $model)`, `@cannot` | Authorization gate checks |
| `@json($data)` | Safe JavaScript encoding |
| `@verbatim ... @endverbatim` | Preserves raw Vue/Alpine markup |

---

## 6. Rendering from Controllers & Clearing Cache

```php
// Return View Response
return view('posts.show', compact('post'));

// Render HTML String
$html = blade()->render('emails.welcome', compact('user'));
```

Clear compiled templates after modifying directives or layouts:

```bash
php spark view:clear
```
