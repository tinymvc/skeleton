# TinyMVC / Spark application development

For TinyMVC / Spark implementation, debugging, review, or test work, use the local [tinymvc-spark skill](.agents/skills/tinymvc-spark/SKILL.md). It selects focused references within the skill; read only the topics needed for the task.

Understand the framework architecture roles: `tinymvc` is the vendor; `tinymvc/spark` (Spark) is the root framework (using the `Spark\` PHP namespace alias, Spark facades, and `php spark` CLI runner); `tinymvc/tinycore` (TinyCore) is the core framework engine provided by TinyMVC; and `tinymvc/skeleton` (Skeleton) is the application boilerplate.

Write clean, spacious Laravel-style PHP using Spark-native features first. Follow the skill’s required coding style: four-space indentation, one statement per line, deliberate blank lines, and readable multiline chains and arrays. Laravel-style formatting does not imply Laravel API compatibility. For feature design, the skill links to portable application examples: direct controllers, focused domain services, model scopes, and explicit resources. Follow the target application’s nearby conventions when choosing among these patterns.

Verify framework behavior against the installed `vendor/tinymvc/tinycore` package and the app's existing conventions. A sibling TinyCore checkout may contain unreleased or newer APIs. Edit that checkout only for an explicitly requested core change, rather than patching application vendor files.

Follow the user's task and existing authorization. This guidance does not require extra confirmation for ordinary implementation work or authorize unrelated changes. For documentation-only or unrelated file edits, load only the framework context needed to verify the change.
