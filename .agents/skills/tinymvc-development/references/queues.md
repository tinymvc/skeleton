# Queues

Contents:

- [Queue and Jobs](#queue-and-jobs)

Read this reference for queue and jobs. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Queue and Jobs

Database queue operations use the configured connection, including their transactions. Default-connection dispatch participates in the business transaction; named connection/file/Redis dispatch does not. MySQL `pushOnce()`/`dispatchOnce()` rejects active transactions; call it after commit so deduplication does not outlive its advisory lock. Queue delivery is at least once: handlers must be idempotent, and stale recovery must exceed the longest execution time. File storage is for local, modest backlogs. Redis queue transitions are atomic and target standalone Redis, not Redis Cluster. Stop workers before clearing or changing storage. `getConnection()` throws for file queues.

Prefer class jobs for application work. A class job implements `Spark\Queue\Contracts\JobInterface` and usually uses `Spark\Queue\Dispatchable`.

```php
<?php

namespace App\Jobs;

use Spark\Queue\Contracts\JobContract;
use Spark\Queue\Contracts\JobInterface;
use Spark\Queue\Dispatchable;
use Throwable;

class SendWelcomeEmail implements JobInterface
{
    use Dispatchable;

    public int $tries = 3;

    public array $backoff = [120, 300];

    public function __construct(private int $userId)
    {
    }

    public function handle(): void
    {
        // send email
    }

    public function failed(JobContract $job, Throwable $exception): void
    {
        // called only after the queue exhausts all tries
    }
}
```

Class job dispatch:

```php
use App\Jobs\SendWelcomeEmail;
use App\Jobs\SyncReports;

SendWelcomeEmail::dispatch($userId)->onQueue('emails');
SendWelcomeEmail::dispatch($userId)->onQueue('emails')->delay(60);

SyncReports::dispatchOnce()
    ->onQueue('reports')
    ->repeatEveryMinutes(5);
```

`Dispatchable::dispatch(...$arguments)` passes arguments to the job constructor. The queue worker later calls `handle()` through the application container.

Per-job retry policy can override the worker defaults:

```php
class SyncReports implements JobInterface
{
    use Dispatchable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900];

    public function handle(): void
    {
        // sync reports
    }
}
```

Retry policy notes:

- `$tries` overrides the `Queue::work(tries: ...)` value for that job.
- `$backoff` overrides the `Queue::work(delay: ...)` value for retry scheduling.
- `$backoff` values are seconds.
- Array backoff is selected by failed attempt number; extra attempts reuse the last value.
- Use lowercase `$backoff`, not `$backOff`.
- Invalid or missing values fall back to the worker defaults.

The fluent dispatch object supports:

- `onQueue('name')`
- `once()` for duplicate-safe push behavior
- `delay($seconds)`
- `schedule($time)` accepts a string or `Carbon`; persisted schedules use UTC and preserve the supplied instant across time zones.
- `repeat($intervalOrAlias)`
- `repeatEveryMinutes($minutes)`
- `repeatHourly()`, `repeatDaily()`, `repeatWeekly()`, `repeatMonthly()`
- `send()` or `dispatch()` to push immediately

The pending dispatch is also pushed automatically when the fluent expression falls out of scope, so this is valid:

```php
SendWelcomeEmail::dispatch($userId)->onQueue('emails');
```

The older job wrapper API is still valid and useful in `bootstrap/app.php`:

```php
job(App\Jobs\SendWelcomeEmail::class)->dispatch('emails');
job(App\Jobs\SyncReports::class)->repeatEveryMinutes(5)->dispatchOnce('reports');
```

In `bootstrap/app.php`, recurring jobs should be registered with `withQueue()` so they use queue `pushOnce()` behavior and do not duplicate every bootstrap:

```php
$app->withQueue(
    jobs: [
        job(App\Jobs\SyncReports::class)->repeatEveryMinutes(5),
    ],
);
```

`withQueue()` accepts `jobs` and an optional `then` callback. Queue logging options were removed from the public queue API; do not pass `log: true`, call `Queue::logging()`, or depend on `storage/logs/queue.log`.

Repeat constants live on `Spark\Queue\Job`:

```php
use Spark\Queue\Job;

job(App\Jobs\SyncReports::class)->repeat(Job::REPEAT_DAILY);
job(App\Jobs\SyncReports::class)->repeat('weekly'); // alias for Job::REPEAT_WEEKLY
```

Supported repeat aliases:

- `hourly` -> `Job::REPEAT_HOURLY`
- `daily` -> `Job::REPEAT_DAILY`
- `weekly` -> `Job::REPEAT_WEEKLY`
- `biweekly` -> `Job::REPEAT_BIWEEKLY`
- `monthly` -> `Job::REPEAT_MONTHLY`
- `quarterly` -> `Job::REPEAT_QUARTERLY`
- `yearly` -> `Job::REPEAT_YEARLY`

The queue connection is selected by `config('queue.default')` (`QUEUE_CONNECTION`), which names an entry in `queue.connections`; that entry's `driver` selects `database`, `file`, or `redis`.

```php
return [
    // The default queue connection name
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
        ],
        'file' => [
            'driver' => 'file',
            'path' => dirname(__DIR__) . '/storage/framework/queue.d',
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

Important:

- Use `dispatchOnce()` or `withQueue(jobs: [...])` for scheduler/cron-style repeated jobs.
- Public job properties `$tries` and `$backoff` override worker retry defaults when present.
- Job `failed()` hooks are called by `Queue` only after all tries are exhausted, not on every retryable exception.
- A `failed()` method may accept either `Throwable $exception` or `JobContract $job, Throwable $exception`.
- The queue connection comes from `config('queue.default')`; do not invent Laravel-style `onConnection()` usage.
- Queue has separate config from cache.
- Database, file, and Redis drivers should behave consistently for push/pushOnce/work. Database jobs have no created_at column. Treat job metadata fields as driver-dependent unless explicitly guaranteed.

