## Confirmed findings (non-withdrawn), by severity:

- **File:** `src/LetMeDown.php` (line 1720)
- **Severity:** high
- **Category:** Functional Bug
- **Description:** In `findMarkdownHeadingMatches()`, when a Setext heading is matched, the loop index `$i` is advanced by 2 inside the loop body to skip the underline line, but the offset accumulator `$offset` is not advanced to account for the length of that skipped underline line (`$nextLine` and `$nextLineEnding`).
- **Failure Mode:** Any headings or blocks processed after a Setext heading in the document will have incorrect start offsets (`lineStart`), being shifted left by the length of the underline line. This causes block slicing (`substr($markdown, $start, $end - $start)`) to align incorrectly, resulting in corrupted and truncated block markdown payloads.

- **File:** `src/LetMeDown.php` (line 2906)
- **Severity:** high
- **Category:** Security
- **Description:** For fields of type `list`, the raw list item HTML (`$item['html']`) is stored and exposed through `FieldData::buildItemsCollection()` (and subsequently projected verbatim via `PlainDataProjector::fieldItemData()`) without any HTML sanitization or URI scheme neutralization of unsafe protocols (like `javascript:`).
- **Failure Mode:** If a list item contains an unsafe markdown link such as `[click](javascript:alert(1))`, the rendered list item HTML preserves the payload verbatim, leading to Cross-Site Scripting (XSS) when a host application iterates over the items and renders their HTML.

- **File:** `src/LetMeDown.php` (line 2907)
- **Severity:** medium
- **Category:** Security
- **Description:** In `FieldData::buildItemsCollection()`, the raw `$item` array containing the unsanitized `links` array is passed directly as the `data` parameter to the `ContentElement` constructor for list items.
- **Failure Mode:** Accessing internal link attributes of list items via element properties (e.g. `$field->items()[0]->data['links'][0]['href']`) returns the raw unsanitized unsafe URIs (e.g. `javascript:alert(1)`), bypassing the security boundaries established for standard standalone links.
