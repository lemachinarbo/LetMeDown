## Confirmed findings (non-withdrawn), by severity:

- **File:** `src/LetMeDown.php` (line 1170)
- **Severity:** low
- **Category:** Edge Case
- **Description:** Headings inside HTML code blocks (using `<pre><code>` tags) are not filtered out because `getFencedCodeRanges` only detects Markdown-style code fences (backticks and tildes). If a line starts with a `#` inside an HTML code block, it will still match the ATX heading regex and be incorrectly parsed as a structural heading.
- **Failure Mode:** A fake heading within an HTML `<pre><code>` block will be treated as a structural block heading, leading to incorrect block nesting and queue mismatch when pairing markdown blocks with DOM headings.

---

- **File:** `src/LetMeDown.php` (line 1170)
- **Severity:** low
- **Category:** Edge Case
- **Description:** Headings inside multi-line HTML comments (e.g. `<!-- \n # fake \n -->`) are matched by the ATX heading regex but are not filtered out by `getFencedCodeRanges`.
- **Failure Mode:** Structural headings are incorrectly created from commented-out markdown text, leading to block count mismatch.
