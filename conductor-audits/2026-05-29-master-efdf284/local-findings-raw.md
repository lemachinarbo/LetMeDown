## Confirmed findings (non-withdrawn), by severity:

- **File:** `src/LetMeDown.php` (line 1564)
- **Severity:** high
- **Category:** Functional Bug
- **Description:** In `findMarkdownHeadingMatches()`, when a Setext heading is matched, both the current line and the next line (the underline) are consumed. However, the loop index `$i` is only incremented by 2 in the loop header, which means the next iteration processes the underline line as the "current line". If the line following the underline also matches the Setext underline pattern (e.g. `Title\n=====\n-----\n`), it will match the underline as the text of another Setext heading.
- **Failure Mode:** At runtime, this creates phantom Setext headings made of underline characters and thematic breaks, leading to incorrect heading blocks and structure mismatch.

- **File:** `src/LetMeDown.php` (line 1593)
- **Severity:** high
- **Category:** Functional Bug
- **Description:** In `findMarkdownHeadingMatches()`, the Setext heading underline regex `/^ {0,3}(=+|-+)\s*$/` matches any line consisting only of dashes or equals signs (with up to 3 leading spaces and optional trailing spaces). However, a single dash `-` (or multiple dashes) can also represent an empty list item or bullet point in a list block. As a result, a list item followed by an empty list item (e.g. `- item 1\n-`) is incorrectly matched as a Setext heading.
- **Failure Mode:** At runtime, a standard list structure with an empty bullet/item will have its elements parsed as a Setext heading block instead of list elements, causing structural content mismatch and incorrect rendering.

- **File:** `src/LetMeDown.php` (line 1579)
- **Severity:** high
- **Category:** Functional Bug
- **Description:** In `findMarkdownHeadingMatches()`, Setext headings are assumed to have only a single line of content preceding the underline. However, Markdown allows Setext headings to span multiple lines. Because the code only checks the single line immediately preceding the underline, any preceding lines of a multi-line Setext heading are treated as a separate paragraph or block, while only the last line and the underline are matched as the heading.
- **Failure Mode:** This causes a structural mismatch between the parsed HTML heading elements (which contain all lines) and the heading markdown queue (which only contains the last line), resulting in incorrect block markdown alignment and extraction.

- **File:** `src/LetMeDown.php` (line 1593)
- **Severity:** high
- **Category:** Functional Bug
- **Description:** In `findMarkdownHeadingMatches()`, the Setext heading underline matching regex `/^ {0,3}(=+|-+)\s*$/` does not account for block container prefixes like blockquotes (e.g. `>`). If a Setext heading is inside a blockquote (where the underline line starts with `>`), the regex will fail to match it.
- **Failure Mode:** At runtime, the DOM parser still detects and renders the heading node inside the blockquote, but `findMarkdownHeadingMatches` will miss it. This causes a mismatch in the alignment between the HTML heading nodes and the markdown heading queue, shifting and corrupting the `.markdown` values for all subsequent blocks.
