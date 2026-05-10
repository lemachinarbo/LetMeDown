# Product Guidelines & Security

## Security Principles
- **Safe Mode**: Parsedown safe mode must be enabled by default.
- **HTML Sanitization**: Raw HTML is strictly disallowed unless `allowRawHtml` is explicitly enabled.
- **Path Protection**: The `load()` method must block path traversal attacks when a `basePath` is set.
- **URI Sanitization**: Unsafe URI schemes in links must be sanitized.

## Compatibility Constraints
- **Section Order**: Must remain stable and numeric.
- **Magic Access**: Do not break property access patterns (e.g., `$content->hero`, `$field->items`).
- **Data Shape**: `data()` projection keys must remain consistent to avoid breaking downstream consumers.
- **Subsection Boundaries**: Maintain consistency in `/sub` and `/sub:name` closer behavior.

## Implementation Rules
- **Large-File Safety**: `src/LetMeDown.php` is a high-risk target. Patch in small windows.
- **No Broad Replacements**: Avoid global search-and-replace without tight anchors.
- **Self-Documentation**: Update `AGENTS.md` whenever public APIs or logic boundaries change.
