# Audit Ledger — master — 2026-05-29-61c0c20

**Scope:** commit=61c0c20
**Diff stats:** 3 files changed, 150 insertions(+)
**Jules session:** `sessions/15793500140760959245`
**Audited by:** Jules (remote) + Local Subagent
**Verdict:** Merge after fixes

---

## Critical Issues

None.

---

## High Issues

### [A-001] — High: Unsafe hrefs preserved in markdown property during projection

- **Location:** `src/LetMeDown.php` (Lines 3255-3285)
- **Category:** Security
- **Failure Mode:** Unsafe targets (like `javascript:`) in the `markdown` key are copied directly into the projected data array without applying the URI scheme neutralization used for `href` and `html`. A consumer rendering this projected markdown is vulnerable to XSS.
- **Found by:** Jules
- **Status:** Open

---

## Medium Issues

### [A-002] — Medium: sanitizeLinkHref duplicates existing neutralization logic

- **Location:** `src/LetMeDown.php` (Lines 3287-3308)
- **Category:** DRY
- **Failure Mode:** The sanitization rules match the ones implemented in `sanitizeBlockLinkHref()` and inside the inline link processing blocks. Duplicating this code introduces maintenance risks.
- **Found by:** Jules
- **Status:** Open

### [A-003] — Medium: Type warning/error in sanitizeLinkHref when parse_url returns false

- **Location:** `src/LetMeDown.php` (Lines 3287-3308)
- **Category:** Edge Case
- **Failure Mode:** For extremely malformed input, `parse_url` can return `false`. Because `false !== null`, the code proceeds to call `strtolower(false)`, causing type errors or deprecation warnings in PHP 8.1+.
- **Found by:** Local
- **Status:** Open

---

## Dismissed Findings

| ID | Agent | Claimed Issue | File | Why Dismissed |
| --- | --- | --- | --- | --- |
| D-001 | Both | PHP Parse Error: missing quotes around `</a>` | `src/LetMeDown.php:3277` | The actual file contents are correctly quoted. The missing quote was an artifact of the prompt construction step (HTML tags like `<a>` were stripped/modified by our browser view/tool or string copy). |

---

## Agent Agreement Summary

| Finding | Jules | Local |
| --- | :---: | :---: |
| A-001 | ✓ | ✗ |
| A-002 | ✓ | ✗ |
| A-003 | ✗ | ✓ |
| D-001 | ✓ | ✓ |

Agreement rate: 1/4 findings confirmed by both agents.

---

## Merge Verdict

**[ ] Ready to merge** — No open critical or high issues.
**[x] Merge after fixes** — [1] high issue (XSS bypass in projected markdown) must be resolved before merge.
**[ ] Needs rework** — Fundamental approach has problems. Do not merge until redesigned.
