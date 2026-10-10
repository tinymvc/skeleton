# Models Relations

Contents:

- [Models](#models)
- [Relationships](#relationships)

Read this reference for models, relationships. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Models

Models extend `Spark\Database\Model`.

```php
<?php

namespace App\Models;

use Spark\Database\Model;

class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = ['title', 'body', 'published', 'meta', 'published_at'];

    protected array $casts = [
        'published' => 'boolean',
        'meta' => 'array',
        'published_at' => 'datetime',
    ];
}
```

Defaults:

- Table defaults to snake plural class name if not set.
- Primary key defaults to `id`.
- Timestamps are enabled by default with `created_at` and `updated_at`.
- Timestamp columns are date/datetime parsed when timestamps are enabled.

Mass assignment:

- `fill()` stores supplied attributes; `$fillable` / `$guarded` filter the persistence data. A nonempty fillable list takes precedence.
- With no fillable list, guarded names exclude exact fields. `['*']` is not a wildcard guard in this implementation. Empty guarded/fillable lists allow all fields.
- Validate input and authorize ownership before `create()` or `fill()`. Prefer an explicit fillable list; attributes retained on an object may still be serialized even when excluded from persistence.

Create/update (`$validated` is the result of request validation):

```php
$post = Post::create([
    'title' => $validated->get('title'),
    'body' => $validated->get('body'),
]);

$post = Post::findOrFail($id);
$post->fill($validated);
$post->save();

$post->remove();
```

Querying:

```php
$posts = Post::where('status', 'published')
    ->latest()
    ->take(10)
    ->all();

$post = Post::where('slug', $slug)->first();
$post = Post::findOrFail($id);
$exists = Post::where('email', $email)->exists();
```

Casts:

- `int`, `integer`
- `float`, `double`, `real`
- `decimal:2`
- `string`
- `bool`, `boolean`
- `array`, `json`, `object`
- `collection`
- `date`, `datetime`, `timestamp`
- `encrypted`
- `hashed`
- custom cast class implementing `Spark\Database\Contracts\CastsAttributes`

Accessors/mutators use the classic methods:

```php
use Spark\Database\Model;

class User extends Model
{
    public function getNameAttribute($value): string
    {
        return trim((string) $value);
    }

    public function setNameAttribute($value): string
    {
        return trim((string) $value);
    }
}
```

The getter controls access; the setter runs when preparing storage data, not immediately on assignment. Prefer these or a custom cast over the current `<name>Attribute(): Attribute` dispatch path, which reads a property instead of calling that method. Recheck that implementation when upgrading.

Custom casts implement `get($value)` and `set($value)` with no model/key/context parameters and are constructed without arguments. Null passes through; `decimal:2` produces a formatted string, and date/datetime/timestamp casts produce `Spark\Carbon` values. Hashed values are one-way; encrypted values depend on the application key.

Model behavior to preserve:

- `find()` / `first()` return a model or null; the `OrFail` variants throw. `all()` returns an array, `get()` a Collection, and `save()` / `remove()` booleans.
- Disable timestamps with `protected const USE_TIMESTAMPS = false`; otherwise create both timestamp columns. `CREATED_AT` / `UPDATED_AT` rename them.
- `getChanges()` contains original values of changed fields; read current attributes for new values. Dirty tracking needs a persisted baseline.
- `copy()` clones the primary key too; it is not row replication. `only()` / `except()` return projected model objects, not plain arrays.
- Hidden fields still win over `makeVisible()`. Appended values are added after filtering; only append public output.
- Override protected `events(): Spark\Database\Events` for `created`, `updated`, `deleted`, and `changed` callbacks. They receive no arguments; use `$this`. Bulk builder writes do not dispatch per-row callbacks, and callbacks are not deferred until commit.
- Add public `scopePublished(QueryBuilder $query)` methods for reusable conditions, then call `Post::published()`. Unknown builder methods may execute a query and forward to a Collection.

`getAttributes()` / `getAttribute()` read raw attributes; `hasAttribute()` detects present nulls. `setAttributes()` replaces raw attributes without fill/cast/persistence tracking. `is($other)` strictly compares table, key name, and value, not persisted existence. Instance increment/decrement use the original key and synchronize the in-memory counter after success.

### Model action return values, tap, and pipe

`Model::create()` returns a model, `fill()` / `refresh()` return the same model, and `save()` / `remove()` return booleans. Forwarded instance `update()` and query-builder `update()` / `delete()` return affected-row counts. Inspect the installed implementation before chaining a write into a resource or another model action.

Use global `tap()` to keep the original model after actions:

```php
$post = tap(Post::findOrFail($id), function (Post $post) use ($validated): void {
    $post->fill($validated);

    if (! $post->save()) {
        throw new \RuntimeException('Unable to save the post.');
    }
});
```

The callback runs immediately; its result is discarded. Exceptions propagate. `tap()` does not save automatically, refresh attributes, validate input, start a transaction, or guarantee write success. `create()` already returns a model and needs no wrapper unless more work follows; it internally ignores the boolean from `save()`. Use explicit `fill()` / `save()` and check the result when necessary.

`tap($post)->save()` returns the model and discards the boolean. This proxy applies to one call: `tap($post)->fill($validated)->save()` returns the ordinary `save()` boolean. Use the callback form for multiple steps or write-result checks. `tap($post->save(), ...)` receives a boolean, not a model.

Global `pipe($value, $callback)` instead returns the callback result unchanged:

```php
$title = pipe($post, fn (Post $post): string => $post->title);
```

It accepts one required callback and preserves results such as `null`, `false`, and objects. It does not evaluate a closure-valued input or output automatically. `with($value, $callback)` provides the same transformation behavior and additionally permits omitting the callback. Verify that the installed package includes the newer global `pipe()` before using it.

Models and query builders have no native instance `tap()` / `pipe()` methods. Use the global helpers or ordinary local variables. Collections already have `tap()`, `pipe()`, and `pipeThrough()`; collection `tap()` requires a callback. `Stringable::pipe()` wraps its result in a new Stringable. `Pipeline::pipe()` adds stages to a pipeline. Do not substitute these APIs without checking their different contracts.

## Relationships

Declare public relationship methods using the model's protected helpers:

```php
public function posts(): \Spark\Database\Relation\HasMany
{
    return $this->hasMany(Post::class, foreignKey: 'user_id');
}

public function roles(): \Spark\Database\Relation\BelongsToMany
{
    return $this->belongsToMany(Role::class, table: 'roles_users',
        foreignPivotKey: 'user_id', relatedPivotKey: 'role_id');
}
```

Other helpers are `hasOne`, `belongsTo`, and `hasManyThrough`. Specify keys for custom schemas. `$user->posts` loads/caches results; `$user->posts()` returns a relation for query chaining. Use `User::with('posts')->all()` to avoid one query per parent. `with()` does not filter parent rows; `whereHas()` does, and both may be needed.

- Nested eager loading: `with('posts.comments')` works directly; a keyed callback on that path constrains comments. Keep primary/foreign keys when selecting columns.
- `load()` uses the lazy path and respects cached results / `lazy: false`. Use `with()` or static `loadRelations()` for explicit eager loading; `unsetRelation()` / `reloadRelations()` manage cached results.
- `HasMany::create()`, `firstOrCreate()`, and `createOrUpdate()` accept arrays or `Arrayable` attributes, including `$request->validated()`. They call `toArray()` on `Arrayable` inputs before assigning the parent foreign key, leaving the input object unchanged. Custom `Arrayable` objects need neither array access nor iteration support. `HasOne` inherits these methods. Additional values and `createMany()` records remain arrays.
- `hasMany()->create()` / `save()` assign the parent key; persist the parent first and allow the foreign key in the child fillable list. `hasOne` needs a unique database constraint for enforced one-to-one cardinality.
- `belongsTo()->associate()` / `dissociate()` change the child object; call `save()` to persist.
- Pivot operations: `attach`, `detach`, `sync`, `syncWithoutDetaching`, `toggle`, `updateExistingPivot`. `sync()` returns attached/detached IDs; update existing pivot attributes explicitly. `detach()` without IDs removes all associations for the parent, not related records. Wrap multi-step changes in a transaction when required.
- Pivot table defaults use sorted plural table names, e.g. `roles_users`. Configure `withPivot()` / `wherePivot()` before query execution and reload cached relations after mutations.
- `withCount('posts')` adds `posts_count`; `withSum('posts', 'views')` adds `posts_sum`. Supply aliases to avoid collisions. Use `has()` for parent count filtering; extra comparison arguments on `withCount()` do not implement that filtering.
- `morphWith()` uses an explicit map such as `['post' => ['class' => Post::class, 'relations' => ['user']]]`; do not assume `morphTo()` / `morphMany()` helpers exist.
- Related reads, direct relation `count()`, `has()` / `whereHas()`, and `withCount()` / other aggregates apply the related model's soft-delete scope by default. Use `withTrashed()` / `onlyTrashed()` in each related-query callback to override it. Parent scopes do not propagate to children; see [Soft deletes](soft-deletes.md#soft-deletes) for joined-table boundaries.

`withExists('posts')` adds `posts_exists` as a 0/1 value; aliases (`'posts as has_posts'`) and callbacks are supported with related soft-delete scopes. `load()` and `reloadRelations()` return the same model for chaining.

