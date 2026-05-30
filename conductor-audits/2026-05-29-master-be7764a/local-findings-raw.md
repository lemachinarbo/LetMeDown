## Confirmed findings (non-withdrawn), by severity:

- **File:** `src/LetMeDown.php` (line 550)
- **Severity:** high
- **Category:** Security
- **Description:** The regular expression `/<!--\s*(.*?)\s*-->/m` contains overlapping and ambiguous whitespace matches. Specifically, the leading `\s*`, the lazy `(.*?)` (which can match spaces), and the trailing `\s*` are adjacent. When matching a comment containing a long sequence of spaces without the trailing `-->`, the engine will attempt to backtrack through all permutations of dividing the spaces among the three patterns.
- **Failure Mode:** CPU exhaustion (Denial of Service) or silent regex failure (returning `false` due to exceeding `pcre.backtrack_limit`, causing `findAllMarkers` to fail silently and ignore all markers).

- **File:** `tests/LoadFromStringTest.php` (line 198)
- **Severity:** low
- **Category:** Functional Bug
- **Description:** The test attempts to chain the method call `->field('title')` on the result of `$contentData->section('hero')` before asserting that the section is not null. If the section lookup fails and returns `null`, the test will terminate with a PHP Fatal Error instead of failing cleanly.
- **Failure Mode:** PHP Fatal Error: "Call to a member function field() on null" during test execution if section parsing or lookup fails.
