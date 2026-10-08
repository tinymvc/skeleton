# Application patterns

Use this reference when implementing endpoints, models, resources, services, or jobs. Examples use a fictional posts domain and the skill’s coding style. Adapt them to the installed APIs and existing application contracts.

## Choose the smallest useful structure

- Keep route declarations focused on middleware and controller actions. Match the application's route grouping and naming. Use typed action parameters for request, bound model, and injected services where supported by the installed router/container.
- Keep a short set of rules in `$request->validate(...)`. Use `App\Http\Requests\<Domain>` form requests when rules are substantial or reusable. Neither approach requires a new class for every endpoint.
- Controllers coordinate validation, access checks, persistence, and the response. Domain decisions shared across actions belong in focused services with a clear business responsibility; external clients and stateful collaborators suit injection. Preserve established static helpers for small stateless operations instead of mechanically converting everything to dependency injection.
- Model relationships and reusable filters belong on models. Use typed `scopeName(QueryBuilder $query, ...): QueryBuilder` methods. Keep domain-specific scopes such as `visibleTo` or `withApiData` in the app; they are not built-in Spark methods.
- Use resources for repeated public representations. Small action results can return `json(['data' => ...])` directly. Preserve the endpoint's existing envelope, status codes, and field types.

## A direct controller action

This method belongs in an authenticated controller importing `App\Models\Post`, `Spark\Http\Request`, and `Spark\Http\Response`. The example assumes an integer owner key and a fillable `title` field; adapt both to the actual schema.

```php
public function update(Request $request, Post $post): Response
{
    abort_unless((int) $post->user_id === (int) $request->user('id'), 403);

    $input = $request->validate([
        'title' => ['required', 'string', 'max:180'],
    ]);

    $post->fill(['title' => $input->get('title')]);

    if (! $post->save()) {
        abort(500, 'Unable to save the post.');
    }

    return json([
        'data' => [
            'id' => $post->id,
            'title' => $post->title,
        ],
    ]);
}
```

Match the application's 403/404 policy for inaccessible records. Route binding alone is not authorization. Do not take owner IDs, counters, or lifecycle state from untrusted input just because a model makes them fillable. Check optional values deliberately; validation does not make every field required.

## Query shape and response shape together

Compose visibility and API-loading scopes before pagination. Use direct `query('table')` calls for narrowly scoped existence/count queries. Use either according to whether model behavior and casts are needed. Prefer `exists()` for an existence check rather than loading all rows.

This scope belongs on an application model with a `user_id` column and imports `Spark\Database\QueryBuilder`:

```php
public function scopeOwnedBy(QueryBuilder $query, int $userId): QueryBuilder
{
    return $query->where('user_id', $userId);
}
```

Keep the owner/visibility scope on mutations as well as reads. Group OR conditions so they cannot escape ownership filters. Bound page size at the endpoint according to its contract. Start independent operations from fresh builders.

Load the relations and counts needed by a resource before serialization. For example, an action using `Post::query()->with('author')->withCount('comments')` can return `PostResource::collection($posts)` after pagination. The app must define those relations and resources. In `PostResource`, importing `Spark\Http\Request` and extending `Spark\Http\Resources\JsonResource`:

```php
public function toArray(?Request $request = null): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'author' => $this->whenLoaded('author', AuthorResource::make(...)),
        'comments_count' => $this->whenCounted('comments'),
    ];
}
```

`AuthorResource` is an application resource in the same namespace. `whenLoaded` avoids loading a relation while serializing. Test both loaded and omitted fields, null relations, and query counts for list endpoints. Do not expose a whole model merely to avoid defining the public representation.

## Shared writes and background work

Keep related writes inside the installed `DB::transaction()` API when they must commit together. Check affected-row counts before adjusting derived counters. Choose transaction and locking boundaries around the actual invariant; do not copy a zero-increment write or locking helper as a universal recipe. Verify the selected driver's behavior and add contention/idempotency checks when relevant.

Keep job payloads small: store identifiers and needed scalar data, then resolve current records in `handle()`. Use Spark's `JobInterface` and `Dispatchable` with the installed retry/backoff contract. Decide what a deleted target means, and ensure retrying an external side effect is safe. Dispatch explicitly after the outer commit when a worker must observe committed records; use the [outbox guidance](advanced-workflows.md#commit-dispatch-and-external-effects) when the commit/dispatch crash window matters. Do not assume a built-in `afterCommit()` method; do not substitute `defer()` for durable work.

## Finish the feature

Cover the successful action, invalid input, guest/other-owner access, and stored state. For list endpoints, test visibility, pagination, and serialization without extra queries. For multi-step writes, test rollback and unaffected neighboring records. Reuse the app's test base and fixtures. Keep the existing frontend integration and build it only when relevant files change.

Preserve grouped imports when the file uses them; do not churn imports across unrelated files. Normalize newly written arrows to `fn (...) => ...`, use deliberate blank lines, and break long chains at method boundaries. Keep examples domain-neutral and preserve established business rules.
