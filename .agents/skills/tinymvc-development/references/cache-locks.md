# Cache Locks

Contents:

- [Cache](#cache)
- [Locks](#locks)

Read this reference for cache, locks. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Cache

### Configure storage

Spark 4.0 supports `database`, `file`, and `redis` in `config/cache.php`. The default is `database`; the old standalone `sqlite` storage driver has been removed. SQLite remains a supported database connection.

```php
return [
    // The default cache store name
    'default' => env('CACHE_STORE', 'database'),

    // The cache stores setup for your application.
    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => env('DB_CACHE_TABLE', 'caches'),
            'connection' => env('DB_CACHE_CONNECTION'),
            'lock_connection' => env('DB_LOCK_CONNECTION'),
            'lock_table' => env('DB_LOCK_TABLE', 'locks'),
        ],
        'file' => [
            'driver' => 'file',
            'path' => dirname(__DIR__) . '/storage/framework/temp/cache',
            'lock_path' => dirname(__DIR__) . '/storage/framework/temp/locks',
        ],
        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_DATABASE', 0),
            'prefix' => env('REDIS_PREFIX', 'spark'),
        ],
    ],
];
```

`default` names the default entry in `connections`; that entry's `driver` selects the backend (defaulting to the entry name). Locks use the same store. You may add extra named stores (for example a second Redis store with its own `prefix`) and select one with `CACHE_STORE`.

Run the framework migration before using database cache/locks. `caches` contains `key`, `group`, `data`, and `expiration`; `locks` contains `key`, `owner`, and `expiration`. Neither needs `created_at`. Use case-sensitive collations for keys and owners, as in the skeleton migration. Cache values are base64-encoded PHP serialization; keep storage private and trusted. Database key storage includes a namespace hash and two separators, leaving 189 bytes for the application key with the default 255-byte limit.

The file driver uses hashed namespace/shard directories, stable guard files, and atomic replacement. Use a local filesystem with working `flock()` and `rename()`, not NFS/SMB. Optional `file_mode`, `dir_mode`, `guard_timeout`, `gc_interval`, and `fsync` control access, contention, cleanup, and file flushing. Atomic replacement does not by itself guarantee survival of a host power loss.

Redis requires `ext-redis`; it also accepts `socket`, `timeout`, `read_timeout`, and `persistent`. Use distinct prefixes for applications and environments. These drivers target standalone Redis; the multi-key queue scripts are not Redis Cluster support. Unknown storage driver names raise an exception.

### Store and retrieve

```php
$cache = cache('catalog');
$cache->store('featured', $products, '+10 minutes');
$products = $cache->retrieve('featured');
```

Expiration is a date/time string accepted by the cache implementation; prefer explicit relative forms such as `+10 minutes`. A null expiration stores without a configured expiry. `has($key, eraseExpired: true)` and `retrieve($key, eraseExpired: true)` can remove expired entries while checking.

### Compute a missing value

```php
$products = cache('catalog')->remember(
    'featured',
    fn() => Product::where('featured', true)->take(12)->all(),
    '+10 minutes',
);
```

`load()` provides the same cache-or-compute pattern. Make the callback safe to run more than once if concurrent requests miss together. For a critical single computation, coordinate with a [lock](https://tinymvc.github.io/locks).

### Update and invalidate

| Method | Purpose |
| --- | --- |
| `erase($keyOrKeys)` | Remove selected entries |
| `flush()`, `clear()` | Empty this cache namespace |
| `flushIf($condition)` | Conditional clearing |
| `eraseExpired()` | Remove expired entries |
| `storeMany($items, $expire)` | Store several entries |
| `storeManyWithExpiry($items)` | Per-item expiry configuration |
| `add($key, $value, $expire)` | Add when absent according to the driver |
| `increment()`, `decrement()` | Numeric counters |
| `pull($key, $default)` | Retrieve and erase |

Invalidate cached results after changes that affect them. Include relevant tenant, locale, or permission context in keys so data is not accidentally reused for the wrong viewer.

### Inspect and maintain

`ttl()`, `stats()`, `retrieveAll()`, and `getExpired()` expose cache information. `optimize()` performs storage maintenance supported by the driver. `unload_cache($name)` releases a cached service instance in the application; it is different from deleting stored entries.

Use `php spark cache:clear` for the framework's cache-clear command. Review its scope before running it in a live application, especially if cache stores are shared with rate limiting or other transient state.

### Driver considerations

SQLite suits a simple local deployment. Redis supports shared storage across app servers. Cache is not durable business storage: design for a missing or expired value and keep authoritative records in the database. `add()`, `pull()`, and numeric increments coordinate competing writers through database transactions, file guards, or Redis atomic operations. An increment may return false for a missing/non-numeric value or exhausted contention retries. Cache expiry still requires application-level retry and idempotency decisions.

### Batch values and cache misses

```php
$cache = cache('dashboard');
$cache->storeManyWithExpiry([
    'counts' => ['value' => $counts, 'expire' => '+1 minute'],
    'labels' => ['value' => $labels, 'expire' => '+1 day'],
]);
$values = $cache->retrieve(['counts', 'labels']);
```

A missing or expired entry reads as null. `has()` distinguishes a stored null from a missing active key. Expiry checks apply even when `eraseExpired` is false; that flag controls cleanup, not permission to read stale data. `ttl()` reports remaining seconds where available; inspect presence separately rather than using TTL as the value itself.

`storeMany()` shares one expiry across a map; `storeManyWithExpiry()` expects `value` and `expire` for each key. Database cache using the default connection participates in that connection’s transaction; a named connection is separate. File and Redis writes do not join database transactions. Invalidate after a successful business transaction and choose a key that includes the user/tenant scope of the cached result.

## Locks

Locks use the cache driver and namespace. Database locks use a separate connection and commit independently of application transactions. With SQLite, use a file-backed database; do not acquire a lock against the same SQLite file while already holding a write transaction. Use a dedicated lock database where needed. Lock timeouts are leases, not a guarantee that the original worker stopped; use idempotency and constraints as well.

Use locks for critical sections.

```php
lock(name: 'default')->withLock('invoice:' . $invoiceId, function () use ($invoice) {
    // critical work
}, timeout: 10, waitTimeout: 5);
```

Or:

```php
$lock = lock(name: 'default');

if ($lock->lock('report:daily', 30, 5)) {
    try {
        // work
    } finally {
        $lock->unlock('report:daily');
    }
}
```

Locks use the default cache store (`config('cache.default')`) and its entry in `cache.connections`.

