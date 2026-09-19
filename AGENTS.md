# TinyMVC application development

For TinyMVC/Spark implementation, debugging, review, or test work, use the local [tinymvc-development skill](.agents/skills/tinymvc-development/SKILL.md). It routes to relevant sections of [FRAMEWORK.md](FRAMEWORK.md); a small task does not require reading the full reference.

Verify framework behavior against the installed `vendor/tinymvc/tinycore` package and the app's existing conventions. A sibling TinyCore checkout may contain unreleased or newer APIs. Edit that checkout only for an explicitly requested core change, rather than patching application vendor files.

Follow the user's task and existing authorization. This guidance does not require extra confirmation for ordinary implementation work or authorize unrelated changes. For documentation-only or unrelated file edits, load only the framework context needed to verify the change.
