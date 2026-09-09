# Testing

TinyMVC includes a small plain PHP test runner. It requires no testing packages,
plugins, or PHP extensions beyond those used by the code you test. Install the
application's normal dependencies, then run from the application/skeleton root.
TinyCore ships `src/Testing/` only; it has no root `tests/` directory or test command:

```sh
composer install
composer test
# Or run PHP directly:
php tests/run.php
php tests/run.php --testsuite Unit
php tests/run.php --filter HomeTest
php tests/run.php --list-tests
composer test -- --filter HomeTest
```

The runner discovers `*Test.php` files recursively under `tests/`. Each public,
non-static `test*` method with no parameters is a test. Every method gets a new
test object. Tests run sequentially in a stable order. `--filter` matches a
case-sensitive substring of `ClassName::methodName`; `--testsuite` selects `Unit`
or `Feature`. No XML configuration is needed.

Exit codes: `0` for success, `1` for failing tests or no matching tests, `2` for
bootstrap, discovery, or command usage errors. Failures show their message and
source location. Explicit skips are listed separately and do not fail the suite.
PHP warnings/notices become test errors; intentionally
suppressed errors retain PHP's normal suppression behavior. A test with no
assertions fails. Assertions use normal PHP comparisons and exceptions, so they
work even when `zend.assertions=-1`.

## Unit tests

Put tests in `tests/Unit` and extend `Spark\Testing\TestCase`. Unit tests do not
boot the application:

```php
namespace Tests\Unit;

use Spark\Testing\TestCase;
use Spark\Support\Str;

final class StringTest extends TestCase
{
    public function test_snake_case(): void
    {
        $this->assertSame('hello_world', Str::snake('HelloWorld'));
    }
}
```

Common assertions are grouped below. Each accepts an optional final failure message.

| Purpose | Methods |
| --- | --- |
| Identity and value | `assertSame`, `assertNotSame`, `assertEquals`, `assertNotEquals`, `assertEqualsWithDelta` |
| Boolean/null/empty | `assertTrue`, `assertFalse`, `assertNull`, `assertNotNull`, `assertEmpty`, `assertNotEmpty` |
| Arrays and iterables | `assertCount`, `assertContains`, `assertNotContains`, `assertArrayHasKey`, `assertArrayNotHasKey` |
| Types | `assertIsArray`, `assertIsString`, `assertIsInt`, `assertIsFloat`, `assertIsBool`, `assertInstanceOf` |
| Numbers | `assertGreaterThan`, `assertGreaterThanOrEqual`, `assertLessThan`, `assertLessThanOrEqual` |
| Strings | `assertStringContainsString`, `assertStringNotContainsString`, `assertStringStartsWith`, `assertStringEndsWith`, `assertMatchesRegularExpression` |
| Files | `assertFileExists`, `assertFileDoesNotExist`, `assertDirectoryExists` |

Identity and iterable membership are strict. `assertEquals`/`assertNotEquals`
explicitly use PHP's `==`/`!=` value comparison; they are not a full object
comparison library. Numeric comparisons take the expected boundary first and the
actual value second. Use `assertEqualsWithDelta($expected, $actual, $delta)` for
floating-point calculations. `fail($message)` fails immediately.

Override `protected setUp(): void` and `protected tearDown(): void` when needed.
Cleanup runs even when setup or the test fails; both failures are reported if
cleanup also fails. Set expectations before code that should throw:

```php
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('email'); // Message substring; also works by itself.
$this->expectExceptionCode(422); // Strict int/string comparison; also works by itself.
$service->save(['email' => 'invalid']);
```

For a single operation, use `assertThrows($class, $callback, $messageSubstring)`;
it returns the caught exception so you can continue making assertions. Expected
exceptions never swallow assertion failures or explicit skips. Use
`$this->markTestSkipped('reason')` when a test cannot run; teardown still runs and
teardown errors still fail the test. Diagnostic output is captured and displayed
with its test name, and error handlers/reporting and removable output buffers are
restored between tests. Do not close the runner's output buffer.

## Feature tests

In skeleton applications, put feature tests in `tests/Feature` and extend
`Tests\TestCase`. It returns `bootstrap/app.php` from `createApplication()`;
`Spark\Testing\ApplicationTestCase` manages the application lifecycle.

```php
namespace Tests\Feature;

use Tests\TestCase;

final class HomeTest extends TestCase
{
    public function test_home_page(): void
    {
        $this->get('/')->assertOk()->assertSee('TinyMVC');
    }
}
```

Request helpers:

```php
$this->get('/users?page=2');
$this->post('/users', ['name' => 'Jane']);
$this->getJson('/api/users');
$this->postJson('/api/users', ['name' => 'Jane']);
$this->put('/users/1', ['name' => 'Janet']);
$this->patchJson('/api/users/1', ['name' => 'Janet']);
$this->deleteJson('/api/users/1');
$this->options('/api/users');
$this->head('/users');
$this->request('DELETE', '/api/users/1', headers: ['X-Test' => 'yes']);
```

Also available: `patch`, `delete`, `putJson`, and the general `request` method.
HEAD requests return an empty body while retaining the prepared response headers.

Requests run through the real providers, router, middleware, and controllers
without starting an HTTP server. Data is supplied as parsed input. JSON helpers
set `Accept: application/json` and `X-Requested-With: XMLHttpRequest` to match
TinyCore's JSON detection. `json: true` also sets `Content-Type: application/json`.

Responses support:

```php
$response->assertStatus(201);
$response->assertOk();
$response->assertSuccessful(); // Any 2xx status.
$response->assertCreated(); // HTTP 201.
$response->assertNoContent(); // HTTP 204 and empty body.
$response->assertSee('Created');
$response->assertDontSee('secret');
$response->assertContent('Created'); // Exact body.
$response->assertHeader('Content-Type', 'application/json; charset=utf-8');
$response->assertHeaderMissing('X-Debug');
$response->assertJson(['created' => true]); // Complete value, strict types.
$response->assertExactJson(['created' => true]); // Same as assertJson.
$response->assertJsonPath('data.0.id', 1); // Missing is different from null.
$response->assertJsonCount(2, 'data');
$response->assertJsonFragment(['id' => 1]); // Strict key/value fragment at any level.
$response->assertJsonStructure(['data' => ['*' => ['id', 'name']]]);
$response->assertJsonValidationErrors(['email']); // HTTP 422 and non-empty field errors.
$response->assertRedirect('http://localhost/login'); // Exact Location value.

$body = $response->content();
$data = $response->json();
$id = $response->json('data.0.id');
$nativeResponse = $response->response; // Spark\Http\Response
```

Status shortcuts also include `assertAccepted`, `assertUnauthorized`,
`assertForbidden`, `assertNotFound`, `assertUnprocessable`, `assertTooManyRequests`,
and `assertServerError` (500). JSON structure checks allow extra keys; `*` checks
every array item, so an empty list satisfies its wildcard structure. Use
`assertJsonCount` when the list must contain a particular number of items. Registered
application exception handlers run; unexpected exceptions fail the test.
Redirects, `abort()`, failed validation, and early `Response::send()` calls are
captured without exiting the runner.

## Configuration and isolation

`tests/bootstrap.php` sets `$_ENV['APP_ENV'] = 'testing'` before application
creation. Testing mode is CLI-only. TinyCore skips `.env`, environment caches,
and compiled configuration caches in this mode. Config PHP files are evaluated
fresh, and `tests/config.php` is merged once by `Application::create()`, before
providers register. `.env.testing` is not loaded implicitly; put environment
values in `tests/bootstrap.php` and configuration overrides in `tests/config.php`.

Each feature test receives a new application, empty session/cookies, an SQLite
`:memory:` database, and a unique temporary directory. `TEST_STORAGE_PATH` is
provided by the base test case; the supplied config uses it for cache, queue,
uploads, and compiled views. The directory is removed after the test. PHP request
globals, environment values, application/tracer references, and timezone are
restored. Shared Blade data and composers are cleared between tests. Application
singletons and database data persist between requests within one test. If you
override setup/teardown, call the parent methods; call parent teardown in a
`finally` block when your own cleanup can throw.

Sessions use an in-memory array and cookie helpers update the test's cookie
array without emitting headers. Middleware stays enabled, including CSRF:

```php
$this->get('/form');
$this->post('/form', [
    '_token' => session('csrf_token'),
    'name' => 'Jane',
])->assertStatus(302);
```

Set up common request state without disabling middleware:

```php
$this->withHeaders(['X-Tenant' => 'demo'])->withToken('api-token');
$this->withSession(['cart' => []])->withCookie('theme', 'dark');
$this->withCookies(['language' => 'en']);
$this->actingAs($persistedUser)->get('/account')->assertOk();
$this->assertAuthenticated();
$this->flushHeaders();
```

Default headers persist within one test; per-request headers override them
case-insensitively. `actingAs` uses the configured auth service's normal `login`
method. Supply a persisted model matching your application's auth configuration;
it can be reloaded on subsequent requests. `assertGuest` checks unauthenticated
state. These helpers do not create a fake authentication driver.

Database assertions inspect the configured connection without modifying data:

```php
$this->assertDatabaseHas('users', ['id' => 1, 'deleted_at' => null]);
$this->assertDatabaseMissing('users', ['email' => 'missing@example.test']);
$this->assertDatabaseCount('users', 1);
```

Migrations are explicit: call the needed migration's `up()` after
`parent::setUp()`, or create just the schema your test needs. SQLite tests require
`ext-pdo_sqlite`. For external services, bind a small anonymous class or test stub
with the container's `bind()`/`singleton()` methods. Custom static state and
macros remain the responsibility of your tests.

Deferred callbacks run after each successful request without flushing the
runner's buffers. If a request throws unexpectedly, queued callbacks are
discarded immediately, including when the test catches that exception and sends
another request.

## Scope

This is a small application test harness. It does not include automatic
migrations, transactions, factories, mocks, browser automation, data-provider
attributes, parallel execution, or coverage reporting. Plain PHP loops and
small stub objects are sufficient for most tests.

It does not simulate native session IDs, cookie domain/expiry rules, multipart
uploads, raw request-body parsing, or direct `header()`, `setcookie()`, `echo`,
`exit`, `die`, and streaming `Response::file()`/`download()` behavior. Use a
separate HTTP/browser test for those paths. Return response objects from routes
where possible.
