# Responses

Read this reference for responses and redirects. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Responses and Redirects

```php
return response('Saved', 200);
return json(['saved' => true], 201);
return redirect('/login');
return to_route('posts.show', ['id' => $post->id]);
return back()->withErrors(['email' => 'Invalid'])->withInput();
return response('', 204);
```

For APIs, returning arrays is acceptable because `Response::send()` JSON encodes arrays.

For explicit JSON status codes, prefer `json($data, $status)`.

### JSON resources

Extend `Spark\Http\Resources\JsonResource` and override `toArray(?Request $request = null): array`.
Read model/array/object fields with `$this->field`. Return `UserResource::make($user)`
directly, `response($resource, 201)`, or `$resource->response(201, $headers)`.
`UserResource::collection($items)` handles arrays, iterables, and `Paginator`; its
`ResourceCollection` reindexes keys unless `->preserveKeys()` is set. Generators
are materialized. Pagination transforms current-page items and adds `data`, `links`,
and `meta` without modifying the paginator.

Use `when()`, `unless()`, `whenNotNull()`, `whenHas()`, `whenLoaded()`, and
`whenCounted()` to omit conditional fields. Wrap conditional work in closures.
For loaded relations use `$this->whenLoaded('posts', fn($posts) => PostResource::collection($posts))`;
this never lazy-loads. Loaded null relations stay null, and count zero is retained.
Omitted defaults remove fields; explicit null defaults retain them. Group optional
fields with `...$this->when($condition, fn() => ['extra' => $value], [])`.

Top-level resources default to a `data` wrapper. Use instance methods `wrap('user')`,
`withoutWrapping()`, and `additional(['meta' => ...])`; reusable metadata comes from
`with(?Request $request = null): array`. Metadata forces a wrapper, and paginated
collections always use `data`, `links`, `meta`. Nested resources are unwrapped.
`resolve()` and `toJson()` produce filtered, unwrapped data; `responseData()` builds
the HTTP document. `json()` still accepts arrays: use `json(['user' => $resource])`
for nesting. Base resources honor model serialization; explicit field selection
can expose hidden model fields, so select only authorized output.

`JsonResource::normalize($value, $request = null)` provides the same recursive
conversion used by `Response` JSON serialization, including conditional omission,
collections, dates, URLs, and nested resources. It returns unwrapped data;
top-level resource envelopes remain the responsibility of `responseData()`.

