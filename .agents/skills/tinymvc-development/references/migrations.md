# Migrations

Read this reference for migrations and schema. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Migrations and Schema

With `app.debug` disabled, migration, rollback, and fresh commands warn and ask for confirmation before changing the database (default: no). Fresh confirms once for the complete rollback/reapply operation. Pass `--force` for unattended runs.

Migration files return an anonymous class with `up()` and `down()`.

```php
<?php

use Spark\Database\Schema\Blueprint;
use Spark\Database\Schema\Schema;

return new class {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

Useful blueprint methods:

- `id`, `increments`, `bigIncrements`
- integer variants
- `string`, `char`, `text`, `longText`
- `decimal`, `double`, `float`
- `boolean`, `enum`, `json`
- `date`, `dateTime`, `time`, `timestamp`
- `timestamps`, `nullableTimestamps`, `softDeletes`, `rememberToken`
- `foreignId`, `foreign`, `constrained`
- `primary`, `unique`, `index`, `fullText`, `spatialIndex`
- `dropColumn`, `dropIndex`, `dropForeign`, `renameColumn`

Column modifiers:

```php
$table->string('email')->unique();
$table->text('body')->nullable();
$table->boolean('active')->default(true);
$table->timestamp('published_at')->nullable();
$table->foreignId('user_id')->nullable()->constrained()->setNullOnDelete(); // default: nullable=false
```

Column `primary()`, `index()`, `unique()`, `fullText()`, and `spatialIndex()` register indexes on the containing blueprint; driver support varies. Foreign-key builders support `nullable()`, `required()`, `default()`, and `after()` on their attached single column. `foreign('user_id')` finds that named declared column, not the most recently added column; existing/composite columns require separately defined modifiers.

### Generating and applying migrations

```bash
php spark make:migration create_posts_table
php spark make:migration --pivot
php spark migrate
php spark migrate:rollback --step=1
```

`--pivot` (alias `-p`) prompts for the first and second related table names. `users` and `roles` produce a `roles_users` migration with an `id`, `user_id`, `role_id`, and cascading foreign keys. Generation writes a migration file; `php spark migrate` applies it. Related tables must exist first. Add a composite unique constraint yourself when duplicate associations are invalid.

The runner records filenames, type, batch, and applied time in a `migrations` database table, created automatically. Back it up with the application database. Files use `migration_` / `seed_` prefixes; `php spark make:seeder Name` and `php spark migrate --seed` handle seed files. `migrate:fresh` rolls back recorded migrations and replays them; it is destructive, not a read-only verification command.

Create new migrations for deployed schemas instead of rewriting history. SQLite/PostgreSQL wrap each file and its ledger change in a transaction; MySQL DDL can leave partial changes because it implicitly commits. On SQLite, adding/removing foreign keys or primary keys from existing tables needs a deliberate rebuild; there is no `change()` column modifier. Columns are `NOT NULL` by default. Use `nullable()` for optional fields; `nullable(false)` or `required()` restores `NOT NULL`, and the last nullability modifier wins. `required(false)` allows null. Default values do not imply nullable columns. Helpers such as `softDeletes()`, `rememberToken()`, `nullableTimestamp()`, and `nullableTimestamps()` explicitly remain nullable; Spark’s `timestamps()` retains its current-time defaults. Chain `nullable()` on `foreignId()` before `setNullOnDelete()`.

