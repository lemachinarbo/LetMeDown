- **File:** `src/LetMeDown.php` (line ~1628 in the diff, the new `sanitizeBlockLinkHref` method)
- **Severity:** high
- **Category:** Functional Bug
- **Description:** The inlined `sanitizeBlockLinkHref` implementation omits the `$scheme === false` guard that exists in the pre-existing `sanitizeHref` method (line 1785 of the actual file). `parse_url($cleanHref, PHP_URL_SCHEME)` returns `false` — not `null` — on seriously malformed URLs (e.g. `//:`). Without the `if ($scheme === false) { $scheme = null; }` normalization, `$scheme` remains `false`, the `$scheme === null` passthrough check fails, and execution falls to `strtolower((string) false)` which produces `''`. An empty string is not in `['http', 'https', 'mailto', 'tel']`, so the function returns `'#'` — which is actually the desired security outcome — but the control flow diverges from the established contract of `sanitizeHref`. A malformed URL that `sanitizeHref` would treat as scheme-less (and therefore pass through unchanged) is instead blocked here. This is a silent behavioral divergence that will cause relative-like malformed hrefs to be neutralized in block links but preserved in field links, producing inconsistent sanitization between the two paths.
- **Failure Mode:** A link like `[foo](//:/path)` in a field produces its original href unchanged (via `sanitizeHref`), but the same link inside a block produces `href="#"` (via the new method). The inconsistency is invisible at runtime; no exception is thrown and no test covers this case.

---

- **File:** `src/LetMeDown.php` (line ~1612 in the diff, `sanitizeBlockLinkHref`)
- **Severity:** medium
- **Category:** DRY
- **Description:** The new `sanitizeBlockLinkHref` private method reimplements the full normalization pipeline (`html_entity_decode` → `rawurldecode` → control-char stripping → `parse_url` → regex fallback → allowlist check) that already exists verbatim in the public static `sanitizeHref` method. The existing `sanitizeHref` already accepts a configurable `$allowedSchemes` parameter and is already used for both image srcs and iterable field link hrefs. Any future fix to the normalization logic (e.g. a new bypass vector) must be applied in two separate places or one code path will remain vulnerable.
- **Failure Mode:** If a new URI bypass vector is discovered and `sanitizeHref` is patched, `sanitizeBlockLinkHref` remains unpatched and block-level links are still exploitable. The duplication is not cosmetic — it is a maintenance hazard in security-critical code.

---

- **File:** `tests/SecurityXssTest.php` (line 89, `test_unsafe_uris_are_stripped_from_block_links`)
- **Severity:** medium
- **Category:** Security
- **Description:** The new block-level test covers only a single bypass vector (`javascript:alert(1)`), while the existing field-level test (`test_unsafe_uris_are_stripped_from_links`) covers nine vectors including whitespace-prefix bypasses (`\t`, ` `, `\r\n`) and inner-whitespace bypasses (` java\nscript:`). The block-level sanitization code path is identical in structure but the test gives no assurance that these bypass vectors are also neutralized at the block level. If the implementation diverges for block links — as it already does with the `false`-scheme edge case described above — none of the bypass vectors would be caught by this test.
- **Failure Mode:** A whitespace-prefix bypass like `[ javascript:alert(1)]` inside a block heading section will not be caught by this test suite and may pass sanitization undetected if the implementation diverges.
