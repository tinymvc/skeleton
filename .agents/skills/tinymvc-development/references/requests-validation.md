# Requests, Validation, and Input

Read this reference for HTTP requests, form validation, FormRequest classes, and the native `Spark\Http\Input` utility. All source paths and command working directories refer to the application root.

---

## FormRequest: The Primary Validation Standard

Always use dedicated **`Spark\Foundation\Http\FormRequest`** classes for resource creation, update, deletions, and complex state changes. Form requests encapsulate authorization and validation outside of the controller, keeping controller actions remarkably thin, clean, and expressive.

### Defining a Form Request

Create form requests in `app/Http/Requests`:

```php
<?php

namespace App\Http\Requests;

use Spark\Foundation\Http\FormRequest;
use Spark\Facades\Auth;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Returning false automatically aborts with a 403 Forbidden response.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'alpha_dash', 'max:255', ['unique' => ['posts', 'slug']]],
            'content' => 'required|string',
            'category_id' => ['required', 'integer', ['exists' => ['categories', 'id']]],
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
            'published' => 'nullable|boolean',
        ];
    }

    /**
     * Custom error messages (optional).
     */
    public function messages(): array
    {
        return [
            'title.required' => 'A post title is required.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }

    /**
     * Custom attribute names for validator error messages (optional).
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
        ];
    }

    /**
     * Handle actions after successful validation (optional).
     * Ideal for normalizations or derived fields before reaching the controller.
     */
    public function passedValidation(): void
    {
        if (!$this->has('slug') && $this->has('title')) {
            $this->merge(['slug' => \Spark\Support\Str::slug($this->title)]);
        }
    }
}
```

### FormRequest Lifecycle and Controller Injection

Type-hint the `FormRequest` in your controller action parameter:

```php
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Spark\Http\Response;

public function store(StorePostRequest $request): Response
{
    // The request is ALREADY authorized and validated!
    // - If authorize() returned false -> 403 Forbidden was thrown before reaching here.
    // - If rules failed -> 422 Unprocessable Entity was thrown before reaching here.

    // Pass $request->validated() directly to Model::create() - it implements Arrayable!
    $post = Post::create($request->validated());

    return PostResource::make($post)->response(201);
}
```

---

## The `Spark\Http\Input` Class: Smart, Fluent, and Arrayable

When you call `$request->validated()` on a `FormRequest` or `$request->validate([...])` on a `Request`, Spark returns an instance of **`Spark\Http\Input`**.

### Implemented Interfaces & Native Capabilities

`Spark\Http\Input` implements:
- **`Spark\Contracts\Support\Arrayable`**: Directly accepted by any Spark framework API expecting array-like data.
- **`\ArrayAccess`**: Access values like an array: `$validated['title']`, `isset($validated['slug'])`.
- **`\IteratorAggregate`**: Can be looped over directly with `foreach ($validated as $key => $value)`.
- **`Spark\Contracts\Support\Jsonable`**: Can be serialized directly to JSON via `toJson()`.
- **`\Stringable`**: Can be string cast.
- **Magic Property Access**: Access fields as properties: `$validated->title`, `$validated->email`.

### Arrayable Interoperability Across TinyCore

Because `Input` implements `Arrayable`, **you do not need to call `->all()` or `->toArray()`** when passing validated data to native TinyCore components. All of the following accept `array|Arrayable`:

1. **Model Persistence**:
   - `Post::create($request->validated())`
   - `Post::createOrUpdate($request->validated(), uniqueBy: ['slug'])`
   - `Post::firstOrCreate($request->validated()->only('slug'), $request->validated()->except('slug'))`
   - `Post::firstOrNew($request->validated()->only('slug'))`
   - `$post->fill($request->validated())->save()`
   - `$author->posts()->create($request->validated())`
2. **QueryBuilder Operations**:
   - `DB::table('logs')->insert($request->validated())`
   - `Post::whereKey($id)->update($request->validated())`
   - `Post::where($request->validated()->only(['category_id', 'status']))->get()`
   - `Product::upsert($request->validated(), conflict: ['sku'], update: ['price', 'stock'])`
3. **Authentication**:
   - `Auth::attempt($request->validated()->only('email', 'password'), $request->boolean('remember'))`

```php
// BAD / NON-SMART - Redundant ->all() calls:
$post = Post::create($request->validated()->all());
Auth::attempt($request->validated()->only(['email', 'password'])->all());

// GOOD / SMART & ELEGANT - Direct Arrayable passing:
$post = Post::create($request->validated());
Auth::attempt($request->validated()->only('email', 'password'), $request->boolean('remember'));
```

### Fluent Chaining on `Input`

`Spark\Http\Input` provides powerful methods for data shaping that return a new `Input` instance (preserving `Arrayable` capability):

```php
// 1. only() - Filter keys (accepts array or variadic arguments)
$credentials = $request->validated()->only('email', 'password');
$subset = $request->validated()->only(['title', 'content']);

// 2. except() - Exclude keys (accepts array or variadic arguments)
$postData = $request->validated()->except('tags', 'send_notification');

// 3. merge() and mergeRecursive() - Combine data
$postData = $request->validated()->merge([
    'user_id' => Auth::id(),
    'published_at' => now(),
]);

// 4. map() and filter() - Transform values fluently
$cleaned = $request->validated()
    ->filter(fn ($v) => !is_null($v))
    ->map(fn ($v) => is_string($v) ? trim($v) : $v);

// Chaining it all together cleanly:
$post = Post::create(
    $request->validated()
        ->merge(['user_id' => Auth::id()])
        ->except('tags')
);
```

### Typed and Sanitized Value Accessors

`Input` includes typed extraction helpers:

```php
$title     = $request->validated()->string('title');          // string or null
$isActive  = $request->validated()->boolean('is_active');      // bool (handles '1', 'true', 'on', etc.)
$count     = $request->validated()->number('quantity');       // int or null
$price     = $request->validated()->float('price');           // float or null
$email     = $request->validated()->email('contact_email');   // sanitized email string or null
$url       = $request->validated()->url('website');           // sanitized URL string or null
$date      = $request->validated()->date('due_date');         // 'Y-m-d' date string
$tags      = $request->validated()->collect('tags');          // Spark\Support\Collection
$slug      = $request->validated()->slug('title');            // auto-slugified string
$strObj    = $request->validated()->str('title');             // Spark\Support\Stringable
$password  = $request->validated()->password('password', hash: true); // automatically bcrypt/argon2 hashed
```

### Direct Request Utilities

`Spark\Http\Request` (and `FormRequest`) also exposes elegant first-class convenience methods directly:

```php
// Authenticated user and bearer token
$user = $request->user();                    // Current authenticated Model (or null)
$token = $request->bearerToken();            // 'Bearer eyJ...' extracted or null

// Typed request inputs with defaults
$page = $request->integer('page', default: 1);
$remember = $request->boolean('remember', default: false);
$comment = $request->safe('comment', allowedTags: ['<b>', '<i>']); // Strips unwanted HTML
```

---

## Inline Request Validation

Reserve inline `$request->validate([...])` **strictly** for lightweight search filters or simple single-field GET endpoints where a dedicated FormRequest would be excessive:

```php
use App\Http\Resources\PostResource;
use App\Models\Post;
use Spark\Http\Request;
use Spark\Http\Resources\ResourceCollection;

public function index(Request $request): ResourceCollection
{
    // Returns Spark\Http\Input instance
    $filters = $request->validate([
        'q' => 'nullable|string|max:100',
        'status' => ['nullable', 'string', ['in' => ['draft', 'published']]],
    ]);

    $query = Post::query()
        ->when($filters->filled('q'), function ($query) use ($filters) {
            $query->whereContains('title', $filters->string('q'));
        })
        ->when($filters->filled('status'), function ($query) use ($filters) {
            $query->where('status', $filters->string('status'));
        });

    return PostResource::collection($query->latest()->paginate(20));
}
```

---

## Common Validation Rules

Spark includes a rich set of built-in validation rules:

- **Presence**: `required`, `required_if:field,val`, `required_unless:field,val`, `present`, `filled`, `nullable`, `sometimes`, `prohibited`
- **Strings**: `string`, `text`, `char`, `alpha`, `alpha_num`, `alpha_dash`, `ascii`, `starts_with`, `ends_with`, `contains`, `not_contains`, `lowercase`, `uppercase`, `regex`
- **Numbers**: `numeric`, `integer`, `int`, `float`, `decimal`, `digits`, `digits_between:min,max`, `min_digits`, `max_digits`
- **Comparisons**: `min:value`, `max:value`, `size:value`, `between:min,max`, `same:field`, `confirmed`, `gt:field`, `gte:field`, `lt:field`, `lte:field`
- **Database**:
  - `unique`: `['unique' => ['table', 'column', 'ignore_id']]`
  - `exists`: `['exists' => ['table', 'column']]`
  - `not_exists`: `['not_exists' => ['table', 'column']]`
- **Arrays & Lists**: `array`, `list`, `items.*.name`
- **Enums & Sets**: `in`: `['in' => ['draft', 'published', 'archived']]`, `not_in`
- **Web & Format**: `email`, `url`, `ip`, `ipv4`, `ipv6`, `mac_address`, `uuid`, `json`
- **Dates**: `date`, `date_format:format`, `before:date`, `after:date`
- **Files**: `file`, `image`, `mimes:jpg,png,pdf`
- **Booleans**: `boolean`, `accepted`, `declined`
- **Security**: `password`

### Parameter Arrays vs Pipe Strings

Rules can be specified as pipe-delimited strings (`'required|string|max:255'`) or arrays (`['required', 'string', 'max:255']`). Rules that take complex arguments (such as `in`, `unique`, or `exists`) must use associative parameter arrays:

```php
$rules = [
    'role' => ['required', ['in' => ['admin', 'editor', 'author']]],
    'email' => ['required', 'email', ['unique' => ['users', 'email', $userId]]],
    'category_id' => ['required', ['exists' => ['categories', 'id']]],
];
```

---

## File Uploads via Request / Input

Check and process uploaded files:

```php
// Check for file
if ($request->hasFile('avatar')) {
    // Direct move with automatic thumbnail/optimization options
    $path = $request->moveFile('avatar', disk: 'public', options: [
        'uploadTo' => 'avatars',
        'maxSize' => 2048,
        'compress' => 90,
        'resize' => [500 => 500],
    ]);
}
```
