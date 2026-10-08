# Routing Controllers

Contents:

- [Routing](#routing)
- [Controllers](#controllers)

Read this reference for routing, controllers. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Routing

Routes are usually written in `routes/web.php`, `routes/api.php`, or `routes/webhook.php`.

`router()` returns the router. `route()` builds a named-route URL; use the route facade or `router()` to register endpoints:

```php
use Spark\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
Route::post('/users', [UserController::class, 'store'])->middleware('auth');
Route::put('/users/{id}', [UserController::class, 'update']);
Route::patch('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
```

Supported route methods:

```php
Route::get($path, $callback);
Route::post($path, $callback);
Route::put($path, $callback);
Route::patch($path, $callback);
Route::delete($path, $callback);
Route::options($path, $callback);
Route::any($path, $callback);
Route::match(['GET', 'POST'], $path, $callback);
Route::view('/about', 'pages.about');
Route::view('/email-preview', 'emails.welcome');
Route::inertia('/contact', 'Contact', ['key' => 'value']); // Requires the Inertia adapter/provider
Route::redirect('/old', '/new', 301);
Route::fallback(fn() => response('Not found', 404));
```

Route parameters:

```php
Route::get('/posts/{id}', fn(int $id) => "Post $id");
Route::get('/posts/{id?}', fn(?string $id = null) => $id);
Route::get('/files/*', fn() => 'wildcard');
```

Route groups:

```php
use App\Http\Controllers\Api\UserController;

Route::group(['prefix' => 'admin', 'middleware' => ['auth']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::group(['prefix' => 'api', 'middleware' => ['cors'], 'withoutMiddleware' => ['csrf']], function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

Controller grouping:

```php
Route::group(['prefix' => 'users', 'callback' => UserController::class], function () {
    Route::get('/', 'index')->name('users.index');
    Route::post('/', 'store')->name('users.store');
    Route::get('/{id}', 'show')->name('users.show');
});
```

Resource routes:

```php
Route::resource('/posts', PostController::class, name: 'posts');
```

Resource route method map:

- `GET /posts` -> `index`
- `GET /posts/create` -> `create`
- `POST /posts` -> `store`
- `GET /posts/{id}` -> `show`
- `GET /posts/{id}/edit` -> `edit`
- `PUT/PATCH /posts/{id}` -> `update`
- `DELETE /posts/{id}` -> `destroy`

## Controllers

Generated controller stubs usually extend an app-level `Controller` class. Follow existing app convention.

Example API controller:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controller;
use App\Models\Post;
use Spark\Http\Request;

class PostController extends Controller
{
    public function index(): array
    {
        return [
            'data' => Post::latest()->take(20)->all(),
        ];
    }

    public function show(int $id): array
    {
        $post = Post::findOrFail($id);

        return ['data' => $post];
    }

    public function store(Request $request): \Spark\Http\Response
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $post = Post::create($data);

        return json(['data' => $post], 201);
    }

    public function update(int $id, Request $request): array
    {
        $post = Post::findOrFail($id);
        $post->fill($request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]));
        $post->save();

        return ['data' => $post];
    }

    public function destroy(int $id): \Spark\Http\Response
    {
        Post::findOrFail($id)->remove();

        return response('', 204);
    }
}
```

Route callbacks and controller methods can return:

- `Spark\Http\Response`
- string
- integer HTTP status code
- array
- object castable to string
- `Arrayable`

Arrays are JSON encoded by `Response::send()`. The controller example shows the request/persistence shape; apply the app's authentication middleware and ownership authorization before changing private records.

