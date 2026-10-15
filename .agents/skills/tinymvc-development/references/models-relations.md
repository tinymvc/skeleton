# Models and Relationships

Read this reference for models, casts, query scopes, and ORM relationships. All source paths and commands are relative to the application root.

## Models

Application models extend `Spark\Database\Model`.

```php
<?php

namespace App\Models;

use Spark\Database\Model;
use Spark\Database\QueryBuilder;
use Spark\Database\Relation\BelongsTo;
use Spark\Database\Relation\HasMany;

class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'published',
        'published_at',
        'meta',
    ];

    /**
     * Attribute type casting.
     * 'date', 'datetime', and 'timestamp' casts automatically hydrate Spark\Carbon instances.
     */
    protected array $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Local Query Scope.
     * Called via Post::published()
     */
    public function scopePublished(QueryBuilder $query): QueryBuilder
    {
        return $query->where('published', true)->whereNotNull('published_at');
    }

    /**
     * Relation: Post belongs to an author User.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }

    /**
     * Relation: Post has many Comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, foreignKey: 'post_id');
    }
}
```

### Model Defaults & Conventions
- **Table Name**: Defaults to the snake_case plural of the class name (`posts` for `Post`).
- **Primary Key**: Defaults to `id`.
- **Timestamps**: Enabled by default (`created_at` and `updated_at`). Automatically hydrated as `Spark\Carbon` instances.
  - Disable with `protected const USE_TIMESTAMPS = false;`.

### Model Actions & Lifecycle
- **Creation**: `Post::create($validated)` returns a newly persisted, hydrated `Post` instance.
- **First or Create**: `Post::firstOrCreate(['slug' => $slug], ['title' => $title])` finds matching record or creates it.
- **First or New**: `Post::firstOrNew(['slug' => $slug], ['title' => $title])` finds matching record or returns an unpersisted instance.
- **Create or Update**: `Post::createOrUpdate($data, uniqueBy: ['email'], values: ['name' => $name])`.
- **Filling**: `$post->fill($validated)` populates fillable attributes in memory.
- **Saving**: `$post->save()` persists changes and returns a `bool`.
- **Deleting an Instance**: `$post->remove()` deletes a loaded model instance and returns `bool`.
- **Destroy by ID**: `Post::destroy($id)` or `Post::destroy([1, 2, 3])` deletes by primary key(s).

### Native Model Events

Override `protected function events(): Events` on your model using `Spark\Database\Events`:

```php
use Spark\Database\Events;
use App\Jobs\SendAccountMail;
use Spark\Facades\Cache;
use Spark\Facades\Storage;

protected function events(): Events
{
    return Events::make(
        created: fn() => SendAccountMail::dispatch($this->id)->onQueue('emails'),
        updated: fn() => Cache::erase("user:{$this->id}"),
        deleted: fn() => Storage::disk('public')->delete($this->avatar_path),
    );
}
```

Hooks execute automatically on `save()` and `remove()`. `changed` fires whenever `created`, `updated`, or `deleted` triggers.

---

## Native Relationships

Prefer TinyCore's native ORM relationships over manual `join()` or `whereRaw()` queries whenever the relationship expresses the requirement. Use a parameterized low-level query only when there is no useful model/relation or the task explicitly requires a database-specific projection.

### 1. Defining Relationships

```php
// One-to-One
public function profile(): HasOne
{
    return $this->hasOne(Profile::class, foreignKey: 'user_id');
}

// One-to-Many
public function posts(): HasMany
{
    return $this->hasMany(Post::class, foreignKey: 'user_id');
}

// Inverse Many-to-One / Belongs To
public function author(): BelongsTo
{
    return $this->belongsTo(User::class, foreignKey: 'user_id');
}

// Many-to-Many
public function tags(): BelongsToMany
{
    return $this->belongsToMany(
        related: Tag::class,
        table: 'post_tags',
        foreignPivotKey: 'post_id',
        relatedPivotKey: 'tag_id'
    );
}

// Inside User: Has-Many-Through User -> Post -> Comment
public function comments(): HasManyThrough
{
    return $this->hasManyThrough(
        related: Comment::class,
        through: Post::class,
        firstKey: 'user_id',
        secondKey: 'post_id'
    );
}
```

### 2. Querying Relationships

TinyCore provides rich relation-querying methods:

```php
// Eager Loading (avoids N+1 query problems)
$posts = Post::with(['author', 'tags'])->get();

// Nested Eager Loading
$posts = Post::with(['author', 'comments.author'])->get();

// Filter with relation constraints (whereHas)
$authors = User::whereHas('posts', fn($q) => $q->where('published', true))->get();

// Filter rows without related records (whereDoesntHave)
$draftPosts = Post::whereDoesntHave('comments')->get();

// Direct relation column matching (whereRelation)
$adminPosts = Post::whereRelation('author', 'role', 'admin')->get();
$tagPosts = Post::whereRelationIn('tags', 'slug', ['php', 'spark'])->get();

// Relation Counts and Aggregates
$posts = Post::query()
    ->withCount('comments')             // Adds comments_count
    ->withExists('tags as has_tags')    // Adds has_tags boolean
    ->withSum('votes', 'score')         // Adds votes_sum
    ->get();
```

### 3. Many-to-Many Pivot Operations

```php
// Attach related records
$post->tags()->attach([1, 2]);
$post->tags()->attach(1, ['expires_at' => now()->addDays(30)]);

// Sync relations (detaches unlisted, attaches new)
$post->tags()->sync([2, 3]);

// Sync without detaching existing associations
$post->tags()->syncWithoutDetaching([4, 5]);

// Toggle association state (attaches if absent, detaches if present)
$post->tags()->toggle([1, 2]);

// Update attributes on a specific pivot record
$post->tags()->updateExistingPivot(1, ['active' => true]);

// Detach all or specific associations
$post->tags()->detach([1]);
$post->tags()->detach(); // Detaches all
```

---

## Attribute Casting & Dates

Supported `$casts`:
- `boolean` / `bool`
- `integer` / `int`
- `float` / `double` / `decimal:2`
- `string`
- `array` / `json`
- `collection` -> Automatically wraps attribute as `Spark\Support\Collection`
- `date`, `datetime`, `timestamp` -> Automatically converted to `Spark\Carbon`
- `hashed` (auto-hashes passwords on store)
- `encrypted` (auto-encrypts on store, decrypts on access)

Custom Casts via `Attribute`:
```php
use Spark\Database\Casts\Attribute;

protected function name(): Attribute
{
    return Attribute::make(
        get: fn (string $value) => ucfirst($value),
        set: fn (string $value) => strtolower($value),
    );
}
```

> [!IMPORTANT]
> Because date attributes and timestamps are cast to `Spark\Carbon`, `JsonResource::normalize()` automatically serializes them to ISO-8601 UTC strings (`toIsoUtcString()`). Never call `Carbon::parse()` on model date attributes in resources.
