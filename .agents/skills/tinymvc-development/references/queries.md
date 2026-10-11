# Queries and Database Operations

Read this reference for database queries, model queries, transactions, and locks. All source paths and commands are relative to the application root.

## Model-First Query Architecture

> [!IMPORTANT]
> **Always Use Model Queries for Domain Entities**:
> When interacting with database tables that have corresponding application models (`User`, `Post`, `Order`, etc.), **always query through the Model**.
> 
> **Never use `DB::table('users')` or `query('users')` for domain models**. Bypassing the model causes severe defects:
> - **Lost Attribute Casting**: Booleans remain integers `0`/`1`, JSON fields remain raw strings, and datetimes are not converted to `Spark\Carbon` instances.
> - **Lost Timestamps**: Bypasses automatic `created_at` and `updated_at` management.
> - **Lost Soft Deletes**: Bypasses `InteractsWithSoftDeletes`, accidentally exposing or overwriting trashed rows.
> - **Lost Relations & Scopes**: Disables `with()`, `whereHas()`, `withCount()`, and domain scopes like `scopePublished()`.
> - **No Hydration**: Returns unhydrated stdClass objects or arrays instead of active Model entities.

```php
// GOOD: Eloquent-style Model querying
$users = User::where('active', true)->latest()->take(20)->get();
$post = Post::findOrFail($id);
$posts = Post::with('author')->paginate(15);
```

### When are `DB::table()` or `query()` Appropriate?
Use raw query builders **only** for:
- Low-level table operations without an associated Model (e.g. migration verification, raw pivot table operations).
- Performance-critical bulk reads that deliberately bypass model hydration.
- Direct database schema maintenance utilities.

---

## Model Querying

```php
// Finding records
$post = Post::find($id);                     // Returns Post|null
$post = Post::findOrFail($id);             // Returns Post or throws 404 NotFoundException
$post = Post::where('slug', $slug)->first(); // First matching record or null
$post = Post::where('slug', $slug)->firstOrFail();

// Collections and Arrays
$posts = Post::where('published', true)->get();      // Returns Spark\Support\Collection<Post>
$postsArray = Post::where('published', true)->all(); // Returns array<Post>

// Ordering and Pagination
$posts = Post::latest()->paginate(20);               // Order by created_at DESC, returns Paginator
$oldest = Post::oldest()->first();                   // Order by created_at ASC

// Aggregates & Existence
$count = Post::where('published', true)->count();
$exists = Post::where('slug', $slug)->exists();
$missing = Post::where('slug', $slug)->doesntExist();
$totalCredits = User::sum('credits');
```

---

## Native ORM Relations vs Raw Joins

> [!TIP]
> **Prefer Native Relations over Manual `join()` or `whereRaw()`**:
> TinyCore features a comprehensive ORM relationship engine. Avoid writing manual SQL joins or raw WHERE clauses when native relations can express the query:

```php
// BAD: Manual joins and where clauses
$posts = DB::table('posts')
    ->join('users', 'users.id', '=', 'posts.user_id')
    ->where('users.role', 'admin')
    ->select('posts.*')
    ->all();

// GOOD: Native Model relation querying
$posts = Post::whereRelation('author', 'role', 'admin')->get();

// GOOD: Filtering with relation closure
$posts = Post::whereHas('comments', fn($q) => $q->where('approved', true))->get();

// GOOD: Eager loading and counts
$posts = Post::query()
    ->with(['author', 'category'])
    ->withCount('comments')
    ->latest()
    ->get();
```

---

## Builder Methods Reference

Common QueryBuilder / Model methods:

- **Selection**: `select('id', 'title')`, `selectRaw('COUNT(*) as total')`, `distinct()`, `column('title')`
- **Conditions**: `where('status', 'active')`, `orWhere('role', 'admin')`, `whereNull('deleted_at')`, `whereNotNull('published_at')`
- **Lists & Ranges**: `whereIn('id', [1, 2, 3])`, `whereNotIn('status', ['archived'])`, `between('age', 18, 65)`
- **Strings**: `whereContains('bio', 'engineer')`, `whereStartsWith('sku', 'SPARK-')`, `whereEndsWith('email', '@example.com')`, `like('name', '%John%')`
- **Dates**: `whereDate('created_at', '2026-10-11')`, `whereYear('created_at', 2026)`, `whereMonth('created_at', 10)`
- **Relations**: `with()`, `withExists()`, `withCount()`, `withSum()`, `withAvg()`, `whereHas()`, `whereDoesntHave()`, `whereRelation()`
- **Ordering**: `orderBy('id', 'DESC')`, `orderDesc('id')`, `orderAsc('name')`, `latest()`, `oldest()`, `random()`
- **Limiting**: `take(10)`, `skip(20)`, `limit(offset: 0, limit: 10)`
- **Terminal Execution**: `first()`, `firstOrFail()`, `get()`, `all()`, `paginate(limit: 20)`, `pluck('name', 'id')`, `value('title')`
- **Mutations**: `insert([...])`, `update([...])`, `delete()`, `increment('views')`, `decrement('stock')`, `upsert(...)`

---

## Upserts and Writes

```php
Product::upsert(
    [
        ['sku' => 'SPARK-01', 'name' => 'Starter', 'price' => 25],
        ['sku' => 'SPARK-02', 'name' => 'Team', 'price' => 50],
    ],
    conflict: ['sku'],
    update: ['name', 'price'],
);
```

- Signature: `upsert($data, ?array $conflict = null, ?array $update = null)`.
- `conflict` specifies the unique constraint columns (defaults to `['id']`).
- `update` specifies the columns to update on conflict (omitted/null updates all non-conflict columns).

---

## Database Transactions and Row Locks

Use `Spark\Facades\DB::transaction()` to execute multi-step database writes atomically:

```php
use Spark\Facades\DB;
use App\Models\User;
use App\Models\Order;

$order = DB::transaction(function () use ($userId, $items): Order {
    $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();

    if ($user->balance < $total) {
        abort(400, 'Insufficient balance.');
    }

    $user->decrement('balance', $total);

    return Order::create([
        'user_id' => $user->id,
        'total' => $total,
        'status' => 'completed',
    ]);
});
```

- If an exception is thrown inside the callback, the transaction is automatically rolled back and the exception rethrown.
- `lockForUpdate()` requests an exclusive SELECT lock (`FOR UPDATE`).
- `sharedLock()` requests a shared read lock (`LOCK IN SHARE MODE` / `FOR SHARE`).
- Locks must always be run within an active transaction to prevent race conditions.
