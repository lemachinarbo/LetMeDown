### MTF-001 - CRITICAL: Link Field Projection Leaks Unsafe Hrefs
* **Location:** `LetMeDown.php` (Lines 2820-2861)
* **Failure Mode:** `PlainDataProjector::fieldData()` emits `href` values from `FieldData` without applying the URI scheme neutralization used by `FieldData::items()`. A link field created from markdown like `[x](javascript:alert(1))` exposes `javascript%3Aalert(1)` through `field()->data()` and projected content arrays.
* **Impact:** Security risk.
* **Required Fix:** Normalize and validate `href` values before projection, or centralize link sanitization so every public representation shares one allowlist path.
* **Required Fixture:** Add a projection test that parses `<!-- links -->\n[XSS](javascript:alert(1))` and asserts `section(0)->field('links')->data()['href'] === '#'` and that encoded or mixed-case schemes are also neutralized.
* **Status:** Resolved

### MTF-002 - CRITICAL: Block Link Collections Preserve Unsafe Hrefs
* **Location:** `LetMeDown.php` (Lines 1439-1529)
* **Failure Mode:** `extractBlockContent()` copies anchor `href` attributes directly into block, section, and content-level link collections without any scheme validation. Markdown links rendered into block collections keep encoded `javascript:` targets in `html` and `data['href']`.
* **Impact:** Security risk.
* **Required Fix:** Apply the same URI normalization and allowlist enforcement used for images and iterable field items before storing `ContentElement` link data.
* **Required Fixture:** Add a block-level security test that parses `# H\n\n[XSS](javascript:alert(1))` and asserts `section(0)->blocks[0]->links[0]->data['href'] === '#'` and `section(0)->blocks[0]->links[0]->html` does not contain `javascript`.
* **Status:** Resolved

### MTF-003 - HIGH: Marker Regex Treats Fenced Code As Parser Syntax
* **Location:** `LetMeDown.php` (Lines 501-550)
* **Failure Mode:** `findAllMarkers()` scans raw markdown comments globally and cannot distinguish literal comments inside fenced code from real parser markers. A code fence containing `<!-- title -->` is parsed as a field marker and removed from user content.
* **Impact:** Silent data corruption.
* **Required Fix:** Stop scanning raw markdown indiscriminately; exclude fenced code and other literal markdown regions before marker classification, or parse markers from a markdown-aware token stream.
* **Required Fixture:** Add a test that parses ```` ```html\n<!-- title -->\n``` ```` and asserts no field named `title` is created and the literal comment remains in rendered or text output.
* **Status:** Resolved

### MTF-004 - HIGH: Heading Queue Misreads Fenced Hash Lines As Real Headings
* **Location:** `LetMeDown.php` (Lines 1090-1112)
* **Failure Mode:** `parseBlocks()` builds its markdown heading queue with `/^(#{1,6})\s+/m` over raw markdown, so `#` lines inside fenced code are treated as structural headings even when the DOM contains no corresponding heading node. That desynchronizes block markdown assignment from the actual rendered heading tree and can drop later real headings from the block model.
* **Impact:** Silent structural parse failure.
* **Required Fix:** Derive block markdown slices from markdown tokens that exclude code fences, or align block splitting to headings that survive markdown rendering instead of raw regex matches.
* **Required Fixture:** Add a test that parses ```` ```md\n# fake\n```\n\n# real\nBody ```` and asserts the section contains a block with heading `real` and no structural heading is created from the fenced line.
* **Status:** Resolved

### MTF-005 - HIGH: Malformed Nested Fields Leak Control Markers Into Field Payloads
* **Location:** `LetMeDown.php` (Lines 623-719)
* **Failure Mode:** `buildFieldRanges()` recovers from out-of-order or overlapping field closers by slicing unmatched spans instead of rejecting or isolating them. With `<!-- a --> ... <!-- b --> ... <!-- /a -->`, field `a` absorbs the `<!-- b -->` marker and field `b` absorbs the `<!-- /a -->` marker as user content.
* **Impact:** Silent data corruption.
* **Required Fix:** Enforce balanced nesting rules for field ranges and either discard malformed overlaps or report them as invalid instead of folding parser control comments into extracted field markdown.
* **Required Fixture:** Add a malformed nesting test that parses `<!-- a -->\nA\n<!-- b -->\nB\n<!-- /a -->` and asserts no extracted field markdown contains parser comment syntax.
* **Status:** Resolved

### MTF-006 - HIGH: Duplicate Subsection Names Overwrite Earlier Content
* **Location:** `LetMeDown.php` (Lines 1030-1078)
* **Failure Mode:** `extractDefaults()` stores parsed subsections in `$subsectionsData[$range['name']]` without a first-win or duplicate-detection guard. When the same subsection name appears twice, the later subsection silently replaces the earlier one.
* **Impact:** Silent data loss.
* **Required Fix:** Apply an explicit duplicate policy for subsection keys, preferably first-win to match top-level section and field behavior, or raise a parse error on duplicates.
* **Required Fixture:** Add a subsection duplication test that parses two `<!-- sub:dup -->` regions and asserts the chosen duplicate policy, including preservation of the first subsection if first-win is intended.
* **Status:** Open

### MTF-007 - MEDIUM: Setext Headings Are Dropped From Block Structure
* **Location:** `LetMeDown.php` (Lines 1094-1112)
* **Failure Mode:** `parseBlocks()` only recognizes ATX headings with `/^(#{1,6})\s+/m` when building markdown block slices, so Setext headings like `Title\n=====` are not represented in the block heading model. The rendered section contains heading text, but the block ends up with `heading === null`.
* **Impact:** Silent structural mismatch.
* **Required Fix:** Extend heading detection to Setext syntax or derive heading boundaries from the rendered DOM instead of a raw markdown regex subset.
* **Required Fixture:** Add a test that parses `Title\n=====\n\nPara` and asserts the first block heading text is `Title` with level `1`.
* **Status:** Open

### MTF-008 - MEDIUM: Reserved Magic Properties Are Shadowed By Field Names
* **Location:** `LetMeDown.php` (Lines 2312-2335)
* **Failure Mode:** `Section::__get()` resolves subsection names and field names before generic collections such as `links`, `images`, `lists`, and `blocks`. A field named `links` changes the meaning of `$section->links` from the structural link collection to the field payload with no validation or warning.
* **Impact:** Silent API breakage.
* **Required Fix:** Reserve framework property names from content keys or require explicit accessor methods for generic collections so field names cannot shadow them.
* **Required Fixture:** Add a collision test that parses a field named `links` alongside normal section links and asserts both the field accessor and the structural collection remain independently accessible.
* **Status:** Open

### MTF-009 - MEDIUM: Binding Extraction Assumes Asterisk Emphasis Only
* **Location:** `LetMeDown.php` (Lines 424-430)
* **Failure Mode:** Binding extraction uses `/\*+([^*]+)\*+/` and only captures the first asterisk-delimited emphasis span inside a binding field. Bindings using underscore emphasis or multiple emphasized segments return a null or partial `atomicValue` even though the markdown is otherwise valid.
* **Impact:** Silent data loss.
* **Required Fix:** Replace the single regex shortcut with markdown-aware emphasis extraction or support both `_..._` and `*...*` forms while defining how multiple emphasized spans are handled.
* **Required Fixture:** Add binding tests for `<!-- field:role -->\n_admin_`, `<!-- field:role -->\n***admin***`, and a binding with two emphasized spans to assert deterministic `atomicValue` behavior.
* **Status:** Open

### MTF-010 - LOW: Compact Field Marker Syntax Is Inconsistently Rejected
* **Location:** `LetMeDown.php` (Lines 510-515)
* **Failure Mode:** `findAllMarkers()` requires literal spaces in the comment regex `'/<!-- (.*?) -->/m'`, while section and subsection parsing accepts compact forms such as `<!--section:hero-->`. As a result, `<!--title-->` is silently ignored even though other marker families accept no-space syntax.
* **Impact:** Silent parse inconsistency.
* **Required Fix:** Unify marker grammar across section, subsection, and field parsing by using the same whitespace-tolerant comment matcher and documenting the accepted syntax.
* **Required Fixture:** Add a syntax consistency test that parses `<!--section:hero-->\n<!--title-->\nHello` and asserts compact field markers follow the same acceptance rule as compact section markers.
* **Status:** Open