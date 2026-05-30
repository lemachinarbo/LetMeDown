## Confirmed findings (non-withdrawn), by severity:

- **File:** `src/LetMeDown.php` (line 2088)
- **Severity:** medium
- **Category:** Functional Bug
- **Description:** Incomplete shadowing resolution in `ContentData`. While the diff reserves property names in `Section`, it fails to do so in `ContentData`. If a document contains a top-level section named after a reserved collection (e.g., `<!-- section:links -->`), magic property lookup `$content->links` will return the `Section` instance instead of the global structural links collection.
- **Failure Mode:** Calling `$content->links` returns a `Section` object rather than a `ContentElementCollection`, causing a runtime error if the caller attempts to iterate or call collection methods.

- **File:** `src/LetMeDown.php` (line 2353)
- **Severity:** medium
- **Category:** Functional Bug
- **Description:** Incomplete shadowing resolution in `Block`. The diff reserves property names in `Section`, but does not apply the same logic to `Block`. If a block contains a field with a reserved name (e.g., `allLinks` or `allImages`), magic property access `$block->allLinks` will return the `FieldData` instance instead of the structural collection.
- **Failure Mode:** Magic access to collection properties (e.g., `$block->allLinks`) returns a `FieldData` object instead of a `ContentElementCollection`, leading to runtime errors if treated as a collection.

- **File:** `src/LetMeDown.php` (line 2353)
- **Severity:** low
- **Category:** Edge Case
- **Description:** Missing `__isset` implementation in `Block`. Although the class implements magic property getter `__get` for collections (`headings`, `allHeadings`, `allImages`, `allLinks`, `allLists`, `allParagraphs`) and fields, it does not define a corresponding `__isset` method.
- **Failure Mode:** Calling `isset($block->allLinks)` or `isset($block->headings)` will return `false` even though the property is set and accessible.
