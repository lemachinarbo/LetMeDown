## Confirmed findings (non-withdrawn), by severity:

- **File:** `src/LetMeDown.php` (line 733)
- **Severity:** high
- **Category:** Functional Bug
- **Description:** When closing a field where multiple nested openers exist in the stack (e.g. `a`, `b`, and `c` are open, and `a` gets closed), the nested loop closes all nested openers at the outer closer's position (`$marker['position']`). Since the start of the next nested opener is not used to truncate the preceding nested opener's range, the preceding nested opener's range will physically overlap and absorb the next nested opener's control comment. For instance, in `<!-- a --> ... <!-- b --> ... <!-- c --> ... <!-- /a -->`, field `b`'s end is set to `<!-- /a -->`'s position, meaning `b`'s content spans across the opener `<!-- c -->`.
- **Failure Mode:** The nested field `b` absorbs the control comment `<!-- c -->` of a nested field inside it, causing parser comment syntax to leak directly into the content payload of field `b`.

- **File:** `src/LetMeDown.php` (line 768)
- **Severity:** high
- **Category:** Functional Bug
- **Description:** In well-formed nested structures where the nested field closes before the parent field (e.g. `<!-- a -->\nA\n<!-- b -->\nB\n<!-- /b -->\nC\n<!-- /a -->`), the closer of the nested field `b` is processed first and popped from the stack. When the parent field `a` is subsequently closed, it is at the top of the stack and is closed from its start to the closer position of `<!-- /a -->`. The range of the parent field `a` spans across the entire inner region, absorbing the control comments `<!-- b -->` and `<!-- /b -->` because no truncation of the parent range occurred when `b` was opened or closed.
- **Failure Mode:** For standard well-formed nested fields, the parent field leaks inner fields' parser control markers directly into its markdown and HTML payload, defeating the purpose of separating nested fields.

- **File:** `src/LetMeDown.php` (line 744)
- **Severity:** medium
- **Category:** Edge Case
- **Description:** If a nested opener is physically adjacent to its parent opener (e.g., `<!-- a --><!-- b -->`), the position of `b`'s opener (`$parentEnd`) is exactly equal to the start position of `a`'s content (`$matchingOpener['start']`). In this case, the condition `if ($parentEnd > $matchingOpener['start'])` evaluates to `false`. As a result, the parent field `'a'` is never added to the `$fieldRanges` array.
- **Failure Mode:** A valid outer field that immediately precedes a nested field (like `a` in `<!-- a --><!-- b -->\nB\n<!-- /a -->`) is completely omitted from the parsed section data, returning `null` when accessed.

- **File:** `tests/LoadFromStringTest.php` (line 92)
- **Severity:** low
- **Category:** Silent Failure
- **Description:** The regression test added to verify nested field marker rejection uses a null-safe property access and fallback default value (`$fieldB?->markdown ?? ''`) to check that the nested field does not contain comments. However, the test does not assert that `$fieldB` is not null.
- **Failure Mode:** If a bug in the parser or range refinement causes the nested field `$fieldB` to be completely lost or omitted (resulting in `null`), the test will silently pass instead of failing.
