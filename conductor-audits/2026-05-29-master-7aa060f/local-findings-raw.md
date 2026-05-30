## Confirmed findings (non-withdrawn), by severity:

- **File:** `src/LetMeDown.php` (line 1160)
- **Severity:** low
- **Category:** Performance Inefficiency
- **Description:** The parser unconditionally executes `parseSectionContent()` for every subsection range in the loop, including duplicates that are destined to be discarded. This performs redundant markdown parsing, DOM loading, block building, and field extraction, which increases CPU and memory consumption unnecessarily.
- **Failure Mode:** Inefficient parsing of duplicate subsection tags.
