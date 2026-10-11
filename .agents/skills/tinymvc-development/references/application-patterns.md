# Application Patterns and Architecture

Use this reference when structuring endpoints, controllers, domain services, models, and JSON resources. Examples follow Spark's native idioms and coding conventions.

## Core Architectural Layers

1. **Routing**: Define clean routes with **Route Model Binding** (`{post}` or `{post:slug}`).
2. **Requests**: Validate and authorize via dedicated **`FormRequest`** classes.
3. **Controllers**: Act as thin coordinators—inject form requests, models, and services, then return JSON resources.
4. **Services**: Encapsulate business logic, multi-step operations, and transactions in dedicated classes under `app/Services`.
5. **Models**: Manage relationships (`hasMany`, `belongsTo`), attribute casting (`$casts`), timestamps, and local scopes (`scopePublished`).
6. **Resources**: Transform entities and collections into API documents via **`JsonResource`**.

---

## 1. Clean Controller Action

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controller;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;

class PostController extends Controller
{
    /**
     * Update an existing post.
     * Route Model Binding automatically resolves Post $post.
     * UpdatePostRequest handles authorization & validation prior to action execution.
     */
    public function update(UpdatePostRequest $request, Post $post, PostService $postService): PostResource
    {
        $updatedPost = $postService->update($post, $request->validated());

        return PostResource::make($updatedPost);
    }
}
```

---

## 2. Dedicated Domain Service (`app/Services`)

Keep controllers thin by moving complex business logic, transactions, and external events into domain services:

```php
<?php

namespace App\Services;

use App\Models\Post;
use Spark\Facades\DB;
use Spark\Http\Requests\Input;
use RuntimeException;

class PostService
{
    /**
     * Update post and synchronize associated tags within a transaction.
     */
    public function update(Post $post, Input $data): Post
    {
        return DB::transaction(function () use ($post, $data): Post {
            $post->fill($data);

            if (!$post->save()) {
                throw new RuntimeException('Failed to update post.');
            }

            if ($data->has('tags')) {
                $post->tags()->sync($data->array('tags'));
            }

            return $post;
        });
    }

    /**
     * Publish post and trigger notifications.
     */
    public function publish(Post $post): Post
    {
        $post->update([
            'published' => true,
            'published_at' => now(),
        ]);

        // Dispatch background jobs or domain events
        // PublishNotificationJob::dispatch($post->id);

        return $post;
    }
}
```

---

## 3. Query Scopes & Native Relations on Models

Encapsulate reusable query logic inside local model scopes and native relationships:

```php
<?php

namespace App\Models;

use Spark\Database\Model;
use Spark\Database\QueryBuilder;
use Spark\Database\Relation\BelongsTo;
use Spark\Database\Relation\BelongsToMany;
use Spark\Database\Relation\HasMany;

class Post extends Model
{
    protected array $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'published',
        'published_at',
    ];

    protected array $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Scope: Filter posts owned by a user.
     */
    public function scopeOwnedBy(QueryBuilder $query, int $userId): QueryBuilder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter published posts.
     */
    public function scopePublished(QueryBuilder $query): QueryBuilder
    {
        return $query->where('published', true)->whereNotNull('published_at');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, foreignKey: 'post_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, table: 'post_tags');
    }
}
```

---

## 4. API Representation via JsonResource

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
            'published' => $this->published,

            // Relations loaded via with() or load()
            'author' => $this->whenLoaded('author', fn() => UserResource::make($this->author)),
            'tags' => $this->whenLoaded('tags', fn() => TagResource::collection($this->tags)),
            'comments_count' => $this->whenCounted('comments'),

            // Carbon instances are automatically converted to ISO-8601 UTC strings!
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

## 5. Complete CRUD Resource Controller

A complete, production-ready controller:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Spark\Http\Request;
use Spark\Http\Resources\ResourceCollection;
use Spark\Http\Response;

class PostController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $posts = Post::with(['author', 'tags'])
            ->withCount('comments')
            ->published()
            ->latest()
            ->paginate(20);

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request): Response
    {
        $data = $request->validated();
        $data['user_id'] = $request->auth()->id();

        $post = Post::create($data);

        return PostResource::make($post)->response(201);
    }

    public function show(Post $post): PostResource
    {
        $post->load(['author', 'tags']);

        return PostResource::make($post);
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $post->fill($request->validated());
        $post->save();

        return PostResource::make($post);
    }

    public function destroy(Post $post): Response
    {
        $post->delete();

        return response()->noContent();
    }
}
```
