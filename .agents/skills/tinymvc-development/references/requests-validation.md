# Requests Validation

Contents:

- [Requests and Validation](#requests-and-validation)

Read this reference for requests and validation. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Requests and Validation

Base request: `Spark\Http\Request`

Form request: `Spark\Foundation\Http\FormRequest`

Form requests validate immediately in the constructor:

```php
<?php

namespace App\Http\Requests;

use Spark\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'published' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A title is required.',
        ];
    }
}
```

Use in a controller:

```php
public function store(StorePostRequest $request)
{
    $data = $request->validated()->toArray();
}
```

Common validation rules:

- `required`, `required_if`, `required_unless`
- `present`, `filled`, `nullable`, `sometimes`
- `email`, `url`
- `string`, `text`, `char`
- `numeric`, `number`, `int`, `integer`
- `array`, `list`
- `min`, `max`, `size`, `between`
- `same`, `confirmed`, `gt`, `gte`, `lt`, `lte` (`ls` / `lse` aliases)
- `in`, `not_in`
- `regex`
- `unique`, `exists`, `not_exists`
- `boolean`, `float`, `decimal`
- `alpha`, `alpha_num`, `alpha_dash`, their `:ascii` forms, and `ascii`
- `digits`, `digits_between`, `min_digits`, `max_digits`
- `date`, `date_format`, `before`, `after`
- `json`, `ip`, `ipv4`, `ipv6`, `mac_address`, `uuid`
- `lowercase`, `uppercase`
- `starts_with`, `ends_with`, `contains`, `not_contains`
- `accepted`, `declined`, `prohibited`
- `file`, `image`, `mimes`
- `password`

Request input helpers:

```php
$request->query('page', 1);
$request->post('email');
$request->input('email');
$request->only(['name', 'email']);
$request->except(['password']);
$request->safe('body', ['p', 'strong']);
$request->input()->boolean('published'); // Input wrapper; this does not validate the field
```

### Optional fields, nested rules, and validated values

```php
$data = $request->validate([
    'username' => ['sometimes', 'string', 'alpha_dash', 'min:3', 'max:30',
        ['unique' => ['users', 'username', auth()->id()]]],
    'share_method' => ['nullable', 'string', ['in' => ['copy_link', 'native_share', 'message']]],
    'settings' => 'sometimes|array',
    'settings.allow_share' => 'sometimes|required|boolean',
    'items' => 'required|array|max:50',
    'items.*.name' => 'required|string|max:100',
    'minimum' => 'required|numeric',
    'maximum' => 'required|numeric|gt:minimum',
]);
```

`sometimes` skips every rule for an absent key. `nullable` permits a present null and skips non-presence rules; it does not override `required`. `filled` rejects supplied empty values, `present` requires the key, and `prohibited` accepts absence or emptiness. Required accepts `0`/`false`. Optional blank strings skip non-presence checks and are not automatically converted to null. Apply presence rules to the parent array and each required child; wildcards expand existing entries. Child rules filter the returned keys of explicitly validated array parents. Errors use concrete dot paths; wildcard messages are supported. Escape literal dots in keys.

Parameter arrays (`['in' => [...]]`, `['unique' => [$table, $column, $authorizedId]]`) preserve lists and string parameters retain case. Unique ignores a numeric `id`, with no custom ignore-column parameter. Field comparisons measure compatible numbers, string lengths, array counts, or uploaded KB; use min/max for literal limits. ASCII validation uses `Str::isAscii()` and needs `voku/portable-ascii`; it rejects rather than transliterates. Integer validation rejects fractional values, scientific-notation strings, and booleans; numeric/float/decimal use `is_numeric()`. Unknown rules still pass, and Laravel Rule objects/bail are unsupported.

`validated()` returns `Input`, even for an empty successful result; `validated($key, $default)` reads a top-level key. Use `Spark\Support\Arr::get($data->toArray(), 'settings.allow_share')` for nested retrieval. Validation failure throws `Spark\Foundation\Exceptions\ValidationException`, replacing `ValidationErrorException`; the application formats JSON 422, browser redirects, or FireLine errors. Browser input flashing requires care with sensitive fields.

Request `integer()`, `float()`, and `boolean()` conversions preserve zero/false instead of replacing them with a default. They do not replace validation. `bearerToken()` returns a token from a valid case-insensitive Bearer header, otherwise null.

`acceptedTypes()` maps MIME types to quality weights, highest first; ties preserve header order. `accept($mime)` requires an explicit positive-quality entry. `accepts($mime)` also honors wildcards and specific `q=0` rejections. Invalid quality weights are zero; media parameters other than `q` are not distinguished. `wantsJson()` checks the highest-ranked acceptable `/json` or `+json` type. `acceptsAnyContentType()` checks a missing/empty Accept header or a preferred positive global wildcard. `expectsJson()` also supports AJAX wildcard requests unless JSON is explicitly rejected, and always treats `/api`, `/api/...`, `/webhook`, `/webhook/...` as JSON paths.

`ip()` trusts forwarded IPs only from peers matching `app.trusted_proxies` (IPv4/IPv6 addresses or CIDRs; skeleton environment: comma-separated `TRUSTED_PROXIES`). The default `app.trusted_proxy_header` is `x-forwarded-for`: scan right to left to the first untrusted address, or use the leftmost when all hops are trusted. Invalid masks never grant trust; malformed chains fall back to the peer. `CF-Connecting-IP` requires explicit `trusted_proxy_header: 'cf-connecting-ip'` / `TRUSTED_PROXY_HEADER` and trusted peers that overwrite that header. Use `'*'` only behind a proxy that sanitizes forwarding data and blocks direct access. These settings affect client IP, not scheme or host.

