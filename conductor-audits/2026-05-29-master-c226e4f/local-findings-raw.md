## Confirmed findings (non-withdrawn), by severity:

- **File:** `src/LetMeDown.php` (line 490)
- **Severity:** medium
- **Category:** Functional Bug
- **Description:** Global Side-Effects / libxml State Manipulation. Calling libxml_use_internal_errors(false) unconditionally overrides the global state of the calling host application.
- **Failure Mode:** Destroys host application's libxml error settings.

- **File:** `src/LetMeDown.php` (line 490)
- **Severity:** medium
- **Category:** Silent Failure
- **Description:** libxml Global Error Buffer Pollution. Missing libxml_clear_errors() call after loading HTML into DOMDocument polluting the shared global buffer.
- **Failure Mode:** Intermittent side-effects or erroneous error reporting in subsequent libxml usage in the host application.

- **File:** `src/LetMeDown.php` (line 490)
- **Severity:** low
- **Category:** Edge Case
- **Description:** Performance Overhead. Instantiating DOMDocument/DOMXPath per-binding field has overhead.
- **Failure Mode:** Low performance in very large documents with thousands of bindings.
