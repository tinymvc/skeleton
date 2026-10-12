# Requests and Validation

Read this reference for HTTP requests, form validation, and FormRequest classes. All source paths and command working directories refer to the application root.

## FormRequest: The Primary Validation Standard

Always use dedicated **`Spark\Foundation\Http\FormRequest`** classes for resource creation, update, and complex actions. Form requests encapsulate authorization and validation outside of the controller, keeping actions clean and thin.

### Defining a Form Request

Create form requests in `app/Http/Requests`:

```php
<?php

namespace App\Http\Requests;

use Spark\Foundation\Http\FormRequest;
use Spark\Http\Auth;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Returning false automatically aborts with a 403 Forbidden.
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
    // If authorize() returned false -> 403 Forbidden was thrown before reaching here.
    // If rules failed -> 422 Unprocessable Entity was thrown before reaching here.

    $post = Post::create($request->validated()); // Note: tinycore/spark supports Arrayable

    return PostResource::make($post)->response(201);
}
```

### Accessing Validated Data

- `$request->validated()` returns a `Spark\Http\Input` instance.
- `$request->validated()->toArray()` or `$request->validated()->all()` returns a clean PHP array of only the validated fields.
- `$request->validated('title')` retrieves a single validated field.
- `$request->validated('is_featured', false)` retrieves with a default fallback.

---

## Inline Request Validation

Reserve inline `$request->validate([...])` **only** for lightweight search filters or simple single-field endpoints where a dedicated FormRequest would be excessive:

```php
public function index(\Spark\Http\Request $request): \Spark\Http\Resources\ResourceCollection
{
    $validated = $request->validate([
        'q' => 'nullable|string|max:100',
        'status' => ['nullable', 'string', ['in' => ['draft', 'published']]],
    ]);

    $query = Post::query()
        ->when(
            $validated->has('q'),
            fn($query) => $query->whereLike('title', "%{$search}%");
        );

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

## Request Input Helpers

For reading raw request data:

```php
$request->query('page', 1);            // Query string parameter
$request->post('email');               // POST body parameter
$request->input('email');              // Query or body parameter
$request->only(['name', 'email']);     // Extract subset of input
$request->except(['password']);        // Exclude specific fields
$request->input()->boolean('active');  // Cast input to boolean
$request->bearerToken();               // Extract token from Bearer header
$request->file('avatar');              // Uploaded file instance
$request->user();                      // Current authenticated user
```
