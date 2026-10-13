# Mail Http

Read this reference for mail and http client. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

*Example Http Request*

```php
$response = \Spark\Facades\Http::withHeaders([CURLOPT_FOLLOWLOCATION => false])
    ->withRetry(0)
    ->withPostFields(['name' => 'spark'], 'application/json')
    ->send('https://foo.bar');

$response->ok();
$response->failed();
$response->json();
```

*Example Mail sending*

```php
$sent = \Spark\Facades\Mail::subject('Welcome to Spark, Please Verify your email')
    ->to($user->email, $user->name)
    ->view('emails.welcome', compact('user'))
    ->send();
```

Helpers/facades:

```php
mailer();
http();
```

HTTP client classes live under `Spark\Http\Client`.

Mail utility depends on optional `phpmailer/phpmailer`.


