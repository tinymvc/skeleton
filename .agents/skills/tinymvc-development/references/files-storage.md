# Files Storage

Read this reference for files and uploads. Source paths and command working directories refer to the application root unless explicitly stated otherwise. Check the installed package when versions differ.

## Files and Uploads

Use **`Spark\Facades\Storage`** for filesystem storage that may be local, public, or S3-compatible. Configuration is in `config/storage.php`; `FILESYSTEM_DISK` chooses the default.

```php
use Spark\Facades\Storage;

// Working with default disk
Storage::put('reports/result.txt', 'Ready');
$contents = Storage::get('reports/result.txt');
$exists = Storage::exists('reports/result.txt');
Storage::delete('reports/result.txt');

// Working with specific disks (public, s3)
Storage::disk('public')->put('avatars/user-1.jpg', $imageContent);
$url = Storage::disk('public')->url('avatars/user-1.jpg');

// Disk uploaders
$path = Storage::disk('public')
    ->uploader('avatars', extensions: ['jpg', 'png'], maxSize: 2048)
    ->upload($request->file('avatar'));
```

- `local` defaults to private `storage/app/private`; `public` uses `storage/app/public` and the existing `storage:link` mapping. Keys are relative paths, never URLs or absolute paths. Traversal is rejected; local child symlinks are not followed.
- Use `putFile($directory, $localPath)` or `putFileAs($directory, $localPath, $name)` for trusted existing local files. These return keys and preserve the source. HTTP uploads must use genuine PHP upload files; size checks use their actual size, not a submitted size value.
- Disk-backed uploaders retain extension/size validation, image resizing, variants, and cleanup. Their driver destinations and returned paths are disk-relative. Staging lives under private `storage/framework/temp/storage-uploads`.
- `Spark\Storage\S3UploaderDriver` implements the existing `UploaderDriverInterface` and delegates to `Spark\Storage\S3Storage`. Configure AWS or compatible endpoints, region, bucket, key/secret, optional session token, optional public/CDN URL, and path-style addressing. ACLs are omitted by default; use `acl: public-read` only for an ACL-enabled public bucket.
- `exists`, `missing`, `get`, `put`, `copy`, `move`, `delete`, `files`, `allFiles`, `size`, `mimeType`, and `lastModified` share the storage API. Storage failures throw; missing-file deletion succeeds. Arrays and moves are not atomic. Local copies stream; S3 copies run on the server and preserve object metadata.
- `path()` is local-only. `url()` requires a configured URL on local storages and does not grant public access on S3. `temporaryUrl($key, $secondsOrDateTime)` is S3-only, signs the origin, and allows 1–604800 seconds. Authorize private downloads first.
- Signed S3 URLs include an attachment content-disposition response override using `basename($key)` as the download filename. Do not modify the signed query. There is no custom filename or inline-display option, and URL generation itself does not check object existence.
- S3 uses SigV4 with cURL; listings use SimpleXML. Single PUTs are limited to 5 GiB; multipart uploads, bucket management, and automatic role-credential discovery/refresh are not implemented. Validate behavior and policy on the selected provider.
- Config files load before application config is merged: use `dirname(__DIR__)` for filesystem defaults there, not `storage_dir()` / `media_url()`. Those helpers are available in running application code.


Request file helpers:

```php
if ($request->hasFile('avatar')) {
    $uploadPath = $request->moveFile('avatar', disk: 'public', options: [
        'uploadTo' => 'avatars',
        'maxSize' => 2048,
        'compress' => 90,
        'resize' => [500 => 500]
    ]);
}
```

Utilities:

- `Storage::disk('public')->uploader(compress: 90)->upload('avatar')`
- `Uploader::make(compress: 90, ...)->upload('avatar')`
- `uploader()->...`
- `Spark\Utils\File` for static local filesystem operations, or `fm()` returning a `File` instance
- `image()`

Inspect existing app usage before implementing uploads.

### S3 direct uploads and downloads

`storage('s3')->temporaryUploadUrl($key, $seconds = 300, $contentType = null, $acl = null)` returns a signed PUT URL (expiry 1–604800 seconds). Send a raw body, with matching Content-Type and x-amz-acl headers when supplied. ACL is explicit, not inferred from disk config. Authorize the key and configure bucket CORS for browser uploads. The URL does not run uploader validation. `downloadFile($key, $localPath)` streams to a temporary file then replaces the local destination on success; failures preserve existing content. The wrapper creates parents. These APIs are S3-only.

`storage('s3')->storage()` exposes the underlying client (local disks return `LocalStorage`); it differs from the uploader `getDriver()`. Low-level `S3Storage::moveFile()` copies then deletes; `deleteDirectory($prefix)` deletes a paginated raw prefix and returns the count. It is not on the disk interface. An empty prefix is rejected; `reports/` scopes a directory while `reports` also matches `reports-old.csv`. Neither operation is atomic and versioned buckets can retain historical versions.




