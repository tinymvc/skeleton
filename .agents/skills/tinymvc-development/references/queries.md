# Queries

Contents:

- [Query Builder](#query-builder)

Read this reference for query builder. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Query Builder

Use `query($table)` or model static calls.

```php
$users = query('users')
    ->where('active', true)
    ->orderDesc('id')
    ->take(20)
    ->all();

$id = query('users')->insert([
    'name' => 'Jane',
    'email' => 'jane@example.com',
]);

query('users')->where('id', $id)->update(['active' => false]);
query('users')->where('id', $id)->delete();
```

Common methods:

- `table`, `from`, `select`, `selectRaw`, `column`
- `where`, `orWhere`, `whereRaw`, `grouped`
- `whereNull`, `whereIn`, `between`, `like`
- `whereDate`, `whereYear`, `whereMonth`
- JSON helpers
- joins
- `orderBy`, `orderAsc`, `orderDesc`
- `limit`, `offset`, `take`, `skip`
- `first`, `firstOrFail`, `last`, `all`, `get`, `paginate`
- `value`, `pluck`, `count`, `exists`, `doesntExist`
- `insert`, `insertOrIgnore`, `insertOrReplace`, `upsert`, `update`, `delete`, `forceDelete`, `restore`, `truncate`
- `updateOrInsert`, `increment`, `decrement`
- `toSql`

Prefer builder methods over string SQL. Bind values; allowlist dynamic column names, sort directions, and SQL expressions. `select()` accepts an array/string or multiple columns. Repeated `orderBy()` calls replace ordering; use a trusted `orderByRaw()` for multiple sort columns.

### Upserts and return values

```php
query('products')->upsert(
    [
        ['sku' => 'SPARK-01', 'name' => 'Starter', 'price' => 25],
        ['sku' => 'SPARK-02', 'name' => 'Team', 'price' => 50],
    ],
    conflict: ['sku'],
    update: ['name', 'price'],
);
```

The signature is `upsert($data, ?array $conflict = null, ?array $update = null)`: separate arrays, not the old combined config array. Null conflict defaults to `['id']`; null/omitted/empty update selects all supplied non-conflict columns. Add a matching unique constraint. MySQL uses actual unique indexes, while SQLite/PostgreSQL use the conflict target. Insert variants return an integer ID, not an affected-row count. PostgreSQL reads it from RETURNING and returns zero for inserts without a numeric primary key; ignored inserts return zero. `update()` / `delete()` / `forceDelete()` return affected-row counts; builder `restore()` returns bool.

`firstOrCreate()` / `updateOrInsert()` are lookup-then-write operations; use unique constraints for concurrent inserts. `insertOrReplace()` emits replacement SQL on MySQL/SQLite and throws on PostgreSQL; use explicit upsert conflict columns there. There is no declared `bulkUpdate()` method.

### State, pagination, and write boundaries

Use a fresh builder for each operation or `copy()` before execution. `first()`, `all()` and `paginate()` reset query state after retrieval; `count()` and `exists()` preserve the builder’s predicates for subsequent reads. Writes clear conditions/bindings. A method forwarded to Collection loads results into memory, so do not assume `chunk()` is database streaming. Mapper callbacks receive the whole result array, not a single row.

`paginate($limit = 10, $keyword = 'page', $fields = null)` uses the query-string page and returns `Spark\Utils\Paginator`. Bound the page size and sort consistently. `items()`, `total()`, `page()`, and `pages()` expose data/metadata. Filter before pagination; grouped `count()` counts groups, and distinct/union totals are preserved. Pagination counts the result before applying limit/offset on every driver.

`update()`, `delete()`, `forceDelete()`, and builder `restore()` require a WHERE condition or an explicit trash scope on a soft-delete model. A bare default model query does not satisfy that guard. Increment/decrement can affect every row in scope; `truncate()` physically empties the whole table regardless of trash scope. Plain table queries do not apply model casts, lifecycle callbacks, or archive filtering.

For nontrivial JSON/date-part SQL, inspect the driver-specific implementation. JSON helpers use field/key/value and text matching on all three drivers; use dot-separated object paths for portable cases. Date-part helpers extract DATE/YEAR/MONTH on all three drivers. Test on the production driver when depending on these differences.

Paginator JSON now contains `current_page`, `data`, `first_page_url`, `from`, `last_page`, `last_page_url`, `links`, `next_page_url`, `path`, `per_page`, `prev_page_url`, `to`, and `total`. Old page/limit methods remain; JSON keys changed. `Paginator::make()` constructs it, and `currentPage()`, `perPage()`, `lastPage()`, `hasMorePages()`, `hasPages()`, `onFirstPage()`, `onLastPage()`, `url()`, `previousPageUrl()`, and `nextPageUrl()` expose navigation. `links` retains Spark link-entry shapes. `through($callback)` returns a mapped array without mutating stored data. Full-array `data(slice: true)` also does not mutate data before serialization.

### Connections and transactions

```php
use Spark\Facades\DB;

$result = DB::transaction(function () use ($userId) {
    return DB::table('posts')->where('user_id', $userId)->update(['published' => true]);
});
```

The facade helper commits the callback result or rolls back/rethrows an exception. It uses the application connection, creates savepoints for nested calls, and has no retry loop. Never manually finish/reconnect the transaction or use implicitly committing DDL inside a callback. `Spark\Database\DB::connection($configOrName)` creates a separate wrapper; use its `table()` and transaction methods consistently. Creating another wrapper does not move models or Schema to it. Schema refreshes its cached PDO/grammar when the application DB is replaced. `connect_db()` returns a builder. `reset()` / `resetPdo()` replace connection state and must not interrupt a transaction.


### Primary keys, aliases, and row locks

`withAlias($column)` qualifies a bare column with the current read alias, or the effective `from()` / table name including `prefix()`. It trims surrounding whitespace, preserves already-qualified names (`p.created_at`, `users.id`) and SQL expressions, and qualifies quoted bare identifiers as well. `latest()` / `oldest()` use it, as do the default `id` orderings of `orderAsc()`, `orderDesc()`, and `last()`. Explicit qualifiers are kept exactly as supplied: use the actual SQL alias once a table has been aliased. This helper does not escape or validate raw SQL expressions; choose ordering expressions in application code. Primary-key and soft-delete predicates retain their separate compilation rules so reads use aliases and writes use physical tables.

`Model::whereKey($id)` filters the model's primary key; an array produces an IN condition and an empty array matches no rows. `whereNotKey($id)` excludes one key with `!=`; an array uses NOT IN, and an empty array excludes nothing while preserving other conditions and model scopes. Both helpers support custom primary keys and relations. `find($id)` / `findOrFail($id)` use the same qualified condition. Read predicates use the current table alias, including an alias assigned after `whereKey()`. Relation builders target the related model's key while retaining the relation's parent/pivot conditions. Plain table queries should use an explicit `where('users.id', $id)`; they have no model key metadata.

Existing model instance writes and locked reads use the original primary value, grouped outside caller OR conditions. Changing an in-memory primary key does not retarget that instance operation. Write predicates target the physical table; this does not add portable joined-update support. Qualify your own ambiguous join columns explicitly.

```php
use Spark\Facades\DB;

DB::transaction(function () use ($userId) {
    $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
    $user->increment('credits', 1);
});
```

`lockForUpdate()` requests an exclusive SELECT lock. `sharedLock()` (or `lock(false)`) requests a shared lock; **false does not disable locking**. `lock(null)` removes the lock clause. MySQL uses `FOR UPDATE` / `LOCK IN SHARE MODE`; PostgreSQL uses `FOR UPDATE` / `FOR SHARE`; SQLite emits no row-lock clause. SQLite transactions do not provide equivalent row-level locking. `lock($trustedSql)` accepts a raw, driver-specific clause.

Lock methods only configure a builder. Execute the SELECT inside a transaction and perform subsequent writes on the same connection before committing. `User::findOrFail($id)->lockForUpdate()->firstOrFail()` scopes the second read to the instance's original key; reading it again under the lock is essential. Prefer the single-read example above. New instances without an original primary value need an explicit condition. Locks do not make lookup-then-insert helpers race-free; retain unique constraints. Avoid lock clauses on aggregates/grouped/union queries unless supported by the target database.

`Spark\Database\DB::connection($configOrName)->transaction($callback)` passes that concrete connection to the callback and returns its result. The facade transaction uses the application connection. Nested calls use savepoints, failures roll back and rethrow, and neither form retries deadlocks automatically. Queries, models, and schema operations must use the intended connection; creating a separate wrapper does not rebind model queries.

### Subqueries

`whereIn($column, $values)`, `whereNotIn()`, `orWhereIn()`, and `orWhereNotIn()` accept `array|string|QueryBuilder|Closure`. Arrays remain bound value lists; the other forms delegate to `whereInSub()` with the corresponding IN/NOT IN and AND/OR behavior. Strings are trusted subquery SQL, not comma-separated values: use `['published']` for a single literal. Empty IN arrays match nothing; empty NOT IN arrays exclude nothing.

`selectSub($subquery, $alias)`, `whereInSub($column, $subquery)`, and `whereNotInSub($column, $subquery)` accept a builder, a closure, or trusted SQL. Builder bindings are imported with distinct parameter names. Closure arguments receive a builder on the same connection; set its table explicitly for a different table.

```php
$authors = query('posts')->select('user_id')->where('published', true);
$users = query('users')->whereIn('id', $authors)->all();

$usersWithPosts = query('users')->whereExists(function ($sub) {
    $sub->table('posts')->selectRaw('1')
        ->whereColumn('posts.user_id', 'users.id');
})->all();
```

`whereExists($subquery, $boolean = 'AND', $not = false)` and `whereNotExists($subquery, $boolean = 'AND')` accept the subquery directly, without a column argument. EXISTS applies to the subquery as a whole; correlate it explicitly with `whereColumn()` as above. Remove the former leading column argument when upgrading. OR variants (`orWhereInSub`, `orWhereNotInSub`, `orWhereExists`, `orWhereNotExists`) are available. Prefer `whereHas()` for model relationships and their built-in scopes.

