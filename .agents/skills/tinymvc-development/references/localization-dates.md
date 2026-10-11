# Localization and Date Boundaries

Read this reference for translations, locale handling, and date manipulation with `Spark\Carbon`. All source paths and commands are relative to the application root.

## Spark\Carbon: Native DateTime Utility

TinyMVC provides **`Spark\Carbon`** as its native date and time library. Use global helpers `now()` and `carbon()` to construct instances.

```php
// Current timestamp
$now = now(); 

// Parsing dates
$date = carbon('2026-10-11 14:30:00');
$parsed = Carbon::parse('next Monday');

// Common calculations
$tomorrow = now()->addDays(1);
$lastWeek = now()->subWeeks(1);
$startOfDay = now()->startOfDay();
$endOfMonth = now()->endOfMonth();
$humanReadable = $post->created_at->diffForHumans();
```

### Automatic API Date Serialization

> [!IMPORTANT]
> **Do NOT Manually Parse or Format Carbon in JsonResource**:
> TinyMVC's `JsonResource::normalize()` automatically detects instances of `Spark\Carbon` and converts them into ISO-8601 UTC strings via `$value->toIsoUtcString()`.
> Because model timestamps (`created_at`, `updated_at`) and attributes cast with `'datetime'` or `'date'` are **already** `Spark\Carbon` instances.

```php
// BAD - Redundant, noisy, and error-prone:
$data = [
    'created_at' => Carbon::parse($this->created_at)->toIsoUtcString(),
    'updated_at' => $this->updated_at?->toIsoUtcString(),
];

// GOOD - Clean and idiomatic Spark:
$data = [
    'created_at' => $this->created_at,
    'updated_at' => $this->updated_at,
];
```

In API responses, always pass the Carbon instance directly to the resource array. Format dates using `format(...)` or `diffForHumans()` only when specifically generating human-readable display strings for views or notification emails.

---

## Translation and Localization

### Language Files

Language files reside in `resources/languages` (configured via `app.locale_dir`):

```php
// resources/languages/en.php
return [
    'greeting' => 'Hello, :name.',
    'welcome' => 'Welcome to TinyMVC!',
    'message_count' => ['%d message', '%d messages'],
];
```

### Translation Helpers

- `__('greeting', ['name' => 'Ada'])`: Returns translated string with placeholders replaced.
- `_e('greeting', ['name' => $name])`: Returns HTML-escaped translation.
- `__('message_count', 2)`: Selects singular/plural from an indexed array.

```blade
{{-- In Blade templates (automatically escaped) --}}
<h1>{{ __('welcome') }}</h1>
<p>{{ __('greeting', ['name' => $user->name]) }}</p>
```

### Switching Request Locale

To dynamically switch locale per request:

```php
$locale = $request->query('lang', 'en');

abort_unless(in_array($locale, ['en', 'bn', 'es'], true), 400, 'Unsupported language.');

app()->instance(
    \Spark\Translator::class,
    new \Spark\Translator(locale_dir($locale . '.php')),
);
```
