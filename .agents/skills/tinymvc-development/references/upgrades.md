# Upgrades

Read this reference for spark 4.0 upgrade. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Spark 4.0 upgrade

This reference targets the 4.0 source. The skeleton dependency is `tinymvc/tinycore: ^4.0`; release tags are still managed separately. Driver configuration, migration history, public/private paths, and removed APIs require an explicit application upgrade.

Back up the database and files. Stop workers/producers and drain old queues before changing storage formats. Migration history is database-only; the core does not read, import, or detect old JSON ledgers. Before running migrations on an existing schema, baseline its applied filenames in the SQL ledger through reviewed application-specific upgrade tooling. An empty ledger means every migration is pending.

Keep historical migrations unchanged. Add a new migration for missing framework tables; do not replace an already-applied users migration with the fresh skeleton file. Rebuild the public uploads link to `storage/app/public` after moving files; private data belongs under `storage/app/private`. Old SQLite cache/queue files and Redis queue indexes are not automatically migrated.

`fireline()`, `Router::fireline()`, and `Request::isFirelineRequest()` have been removed. There is no core `Route::fire()` replacement; return normal views or implement the installed client's response protocol explicitly. Use `app.locale`, `app.locale_dir`, and `locale_dir()` for localization. Existing native session locations, cookie names, and old stateless remember-cookie formats may require fresh logins.

`cache:clear` clears the default cache and compiled views/configuration while preserving active locks and unrelated temporary files. Clear other named caches explicitly with `cache($name)->flush()`. `key:generate` preserves existing application keys.

