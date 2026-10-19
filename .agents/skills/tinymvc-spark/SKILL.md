---
name: tinymvc-spark
description: Implement, debug, review, and test TinyMVC applications powered by TinyCore/Spark. Use for Spark-native PHP, routes, controllers, validation, ORM, JSON resources, Blade, FireLine, Inertia, storage, queues, cache, HTTP, auth, and other framework features, or for explicitly requested TinyCore changes.
---

# TinyMVC Spark Development

The framework skill ships with TinyCore so its guidance matches the installed framework version.

1. From the application root, read [the installed TinyMVC Spark skill](../../../vendor/tinymvc/tinycore/skills/tinymvc-spark/SKILL.md) at `vendor/tinymvc/tinycore/skills/tinymvc-spark/SKILL.md` before implementation, debugging, review, or test work.
2. Follow that skill and load only its relevant references. Resolve its relative links inside `vendor/tinymvc/tinycore/skills/tinymvc-spark/`, not this entry point's directory.
3. Verify APIs against the installed `vendor/tinymvc/tinycore/src` and follow the application's nearby conventions. Laravel-style formatting does not imply Laravel API compatibility.

If `vendor/` is missing, install the project's locked dependencies with `composer install` when dependency setup is in scope; otherwise report the missing dependency. If TinyCore is installed but the skill file is absent, the installed version predates bundled guidance: use its source and local conventions for the current task, and report that limitation. Do not silently upgrade dependencies, fetch newer guidance, or substitute a sibling checkout.

For an explicitly requested TinyCore change, use the target checkout's `skills/tinymvc-spark/SKILL.md` and `src/`. Application vendor files remain reference only.
