# Responses and API Resources

Read this reference for HTTP responses, JSON resources, redirects, and API contracts. All source paths and commands are relative to the application root.

## API Responses and JSON Resources

For APIs, always prefer **`Spark\Http\Resources\JsonResource`** for entity and collection presentation. Do **not** manually construct ad-hoc response arrays such as `json(['data' => [...]])` or `return ['data' => $post]` in controllers.

### Standard Controller Responses

```php
// Single entity (defaults to 200 OK with 'data' envelope)
return PostResource::make($post);

// Created entity (201 Created)
return PostResource::make($post)->response(201);
// or: return response(PostResource::make($post), 201);

// Collection of entities
return PostResource::collection($posts);

// Paginated collection (automatically includes 'data', 'links', and 'meta')
return PostResource::collection(Post::latest()->paginate(20));

// No Content (e.g. after deletion)
return response()->noContent(); // 204 No Content

// Simple scalar/message response (NOT for models or entity data)
return json(['message' => 'Password reset email sent.']);
```

---

## Defining JSON Resources

Extend `Spark\Http\Resources\JsonResource` and implement `toArray(?Request $request = null): array`. Read model attributes and loaded relations via `$this->attribute`.

```php
<?php

namespace App\Http\Resources;

use App\Models\Post;
use Spark\Http\Request;
use Spark\Http\Resources\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    public function toArray(?Request $request = null): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,

            // Relations: use whenLoaded() to avoid unintended N+1 queries
            'author' => $this->whenLoaded('author', fn() => UserResource::make($this->author)),
            'comments' => $this->whenLoaded('comments', fn() => CommentResource::collection($this->comments)),
            'comments_count' => $this->whenCounted('comments'),

            // Dates: do NOT manually call Carbon::parse() or ->toIsoUtcString()!
            // JsonResource::normalize() automatically formats Spark\Carbon instances into ISO-8601 UTC strings.
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // URLs: Spark\Url objects are automatically resolved to URL strings
            'url' => route('posts.show', ['post' => $this->slug]),
        ];
    }
}
```

### Date and Carbon Handling in Resources

> [!IMPORTANT]
> **Automatic Date Serialization**:
> `JsonResource::normalize()` automatically inspects values recursively:
> - Instances of `Spark\Carbon` are automatically converted via `->toIsoUtcString()`.
> - Instances of `DateTimeInterface` are automatically formatted as `DateTimeInterface::ATOM`.
> 
> Because model timestamps (`created_at`, `updated_at`) and attributes cast with `'datetime'` or `'date'` are already `Spark\Carbon` objects.

```php
// BAD - DO NOT DO THIS:
$data = [
    'created_at' => Carbon::parse($this->created_at)->toIsoUtcString(),
    'created_at' => $this->created_at?->toIsoUtcString(),
];

// GOOD - Idiomatic Spark:
$data = [
    'created_at' => $this->created_at,
];
```

---

## Conditional Attributes in Resources

Use built-in conditional helpers to omit keys when conditions are not met:

- **`when($condition, $value, $default = MissingValue)`**: Includes `$value` only if `$condition` is truthy.
- **`unless($condition, $value, $default = MissingValue)`**: Includes `$value` only if `$condition` is falsy.
- **`whenNotNull($value, $default = MissingValue)`**: Includes `$value` only when not null.
- **`whenHas($attribute, $value = MissingValue)`**: Includes value only if the attribute exists on the underlying resource.
- **`whenLoaded($relation, $value = MissingValue)`**: Includes the relation only if it was eager loaded on the model (`$model->relationLoaded($relation)`). Never triggers an unexpected lazy query.
- **`whenCounted($relation)`**: Shorthand for `$this->whenHas($relation . '_count')`.

```php
return [
    'id' => $this->id,
    'secret' => $this->when($request?->user()?->isAdmin(), $this->secret),
    'author' => $this->whenLoaded('author', fn() => UserResource::make($this->author)),
    'tags_count' => $this->whenCounted('tags'),
    ...$this->when($this->is_featured, fn() => [
        'featured_at' => $this->featured_at,
        'badge' => 'Featured',
    ], []),
];
```

---

## Collections and Pagination Envelopes

`PostResource::collection($items)` automatically detects whether `$items` is an iterable collection or a `Spark\Utils\Paginator`:

1. **When given an array or Collection**:
   Wraps items under the top-level `'data'` key:
   ```json
   {
       "data": [
           { "id": 1, "title": "First" },
           { "id": 2, "title": "Second" }
       ]
   }
   ```

2. **When given a `Paginator`** (e.g. `PostResource::collection(Post::latest()->paginate(20))`):
   Automatically produces the standardized paginated response containing `data`, `links`, and `meta`:
   ```json
   {
       "data": [...],
       "links": {
           "first": "https://api.example.com/posts?page=1",
           "last": "https://api.example.com/posts?page=5",
           "prev": null,
           "next": "https://api.example.com/posts?page=2"
       },
       "meta": {
           "current_page": 1,
           "per_page": 20,
           "last_page": 5,
           "total": 95,
           "from": 1,
           "to": 20
       }
   }
   ```
   Do not manually format pagination envelopes; let `ResourceCollection` handle it.

---

## Response Wrapping and Custom Metadata

- **Wrapping**: Top-level resources default to a `'data'` envelope.
  - `UserResource::make($user)->wrap('user')` changes the key to `'user'`.
  - `UserResource::make($user)->withoutWrapping()` removes the outer wrapper.
- **Additional Metadata**:
  - `UserResource::make($user)->additional(['meta' => ['version' => '1.0']])`
  - Or override `with(?Request $request = null): array` inside the resource class.

---

## Web Responses and Redirects

For traditional server-rendered web routes:

```php
// Render a Blade template
return view('posts.show', ['post' => $post]);

// Redirect to a specific URL
return redirect('/dashboard');

// Redirect to a named route
return to_route('posts.show', ['post' => $post->id]);

// Redirect back with validation errors and old input
return back()->withErrors(['email' => 'Invalid credentials'])->withInput();

// Empty 204 No Content response
return response()->noContent();
```
