# Localization and date boundaries

Read for multilingual UI, user-selected locales, time-sensitive jobs, filters, and API timestamps. Inspect installed `Translator.php`, `Carbon.php`, `Foundation/helpers.php`, and the application's locale/timezone configuration.

## Translation shape and escaping

`app.locale` selects the default locale; `app.locale_dir` / `locale_dir()` locate language files, normally `resources/languages`. Files return a PHP array:

```php
return [
    'greeting' => 'Hello, :name.',
    'message_count' => ['%d message', '%d messages'],
];
```

Use `__('greeting', ['name' => 'Ada'])` and `__('message_count', 2)`. Missing keys fall back to the source text/key. Numeric counts choose a singular/other indexed pair; do not assume a full locale-specific plural rules engine or named plural categories.

`__()` returns text; `_e()` returns HTML-escaped text rather than echoing it. Blade's `{{ __('greeting', ['name' => $name]) }}` escapes output. Avoid double escaping or raw HTML output for user-controlled placeholders. Keep translation keys and placeholders consistent across supported languages.

## Request locale and shared state

Select locale before the translator is first resolved. Changing config after a shared translator has loaded files does not reload them. For request-specific selection, validate the language against the application's supported allowlist and replace the service with a translator for that known file:

```php
$locale = $request->query('lang', 'en');

abort_unless(in_array($locale, ['en', 'bn'], true), 400, 'Unsupported language.');

app()->instance(
    \Spark\Translator::class,
    new \Spark\Translator(locale_dir($locale . '.php')),
);
```

Persist preferences only when required. Never derive arbitrary file paths from unchecked input. `addLanguageFile()` defers loading; `mergeTranslatedTexts()` merges values, while `setTranslatedTexts()` replaces the map. Inspect source before changing file precedence or resetting a translator in a worker.

Test missing keys, placeholder escaping, counts of 0/1/many, unsupported locales, and two requests/jobs with different languages to detect shared-state leakage.

## Dates, storage, and display

Use `now()` / `carbon()` and `Spark\Carbon` only with verified installed signatures. Establish the application's storage timezone, input timezone, and display timezone before changing date logic. Keep machine-readable timestamps with explicit offsets at API boundaries; format localized text only for presentation.

For a user's calendar-day filter, compute boundaries in that user's timezone and convert to storage time before querying. A day across daylight-saving changes need not be 24 hours. Use a half-open interval (start inclusive, next start exclusive) where appropriate, and bind values. SQL `whereDate()` uses the database's interpretation of stored values, so it may differ from a user's local day.

Do not assume date objects are immutable or every Carbon/Laravel method exists. Test invalid input, timezone offsets, boundary instants, and daylight-saving transitions relevant to the product. Keep queue availability/expiry calculations consistent with the configured backend and avoid relying on formatted date strings for ordering.
