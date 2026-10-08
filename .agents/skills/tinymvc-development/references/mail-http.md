# Mail Http

Read this reference for mail and http client. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Mail and HTTP Client

Helpers/facades:

```php
mailer();
http();
```

HTTP client classes live under `Spark\Http\Client`.

Mail utility depends on optional `phpmailer/phpmailer`.

The Mail utility no longer exposes framework-specific logging methods. Use normal exception handling, `tracer_log()`, or your app logger around mail sending if mail activity needs to be recorded.

