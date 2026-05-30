# Tracks Ledger

This file tracks the high-level units of work for the project.

## Active Tracks

### Commit Review Series (adversarial audit, one by one)

Commits are listed oldest-first. Each must be audited, any issues fixed, and a
`conductor-audits/` folder written before marking done.

| # | Commit | Subject | MTF | Status |
|---|--------|---------|-----|--------|
| 1 | `61c0c20` | fix: sanitize projected link hrefs | MTF-001 | ✅ Done — fixes committed as `2c44ba6` |
| 2 | `ce2d523` | fix: sanitize block link hrefs | MTF-002 | ✅ Done — fixes committed as `9673789` |
| 3 | `b2e33c1` | fix: ignore fenced code markers | MTF-003 | ✅ Done — fixes committed as `a353973` |
| 4 | `9c0754f` | fix: ignore fenced heading lines | MTF-004 | ✅ Done — no fixes needed |
| 5 | `1bd72ec` | fix: reject malformed field overlap | MTF-005 | ✅ Done — fixes committed as `1786f31` |
| 6 | `7aa060f` | fix: preserve first duplicate subsection | MTF-006 | ⏳ Pending |
| 7 | `efdf284` | fix: preserve setext block headings | MTF-007 | ⏳ Pending |
| 8 | `92bca59` | fix: reserve section magic properties | MTF-008 | ⏳ Pending |
| 9 | `c226e4f` | fix: support binding emphasis variants | MTF-009 | ⏳ Pending |
| 10 | `be7764a` | fix: accept compact field markers | MTF-010 | ⏳ Pending |
| 11 | `b7cd13d` | audit(master): adversarial review merge | — | ⏳ Pending (audit doc only) |

> Fixes for MTF-001 supersede the original `sanitizeBlockLinkHref` duplicate —
> centralized into `LetMeDown::sanitizeHref()` (public static). All 143 tests pass.

## Completed Tracks
- [x] `audit-61c0c20`: Adversarial review of commit 61c0c20 (Session: 15793500140760959245)
- [x] `health-dedup-collections`: Eliminate code duplication in ContentData helper methods (Session: 14695347834809448098)
- [x] `perf-xpath-lists`: Optimize DOMXPath querying inside nested list extraction loops (Session: 16687666074004037682)
- [x] `perf-cache-sections`: Cache getUniqueSections result in helper methods (Session: 16361870555066929219)
- [x] `tests-field-iterator`: Add unit tests for FieldData::getIterator (Session: 9482420760406895587 - Redundant)
- [x] `health-simplify-headings`: Simplify collectHeadingsFromBlock (Session: 11768149152402352772)
- [x] `security-libxml-nonet`: Add LIBXML_NONET to DOMDocument HTML parsing (Session: 6176352234350768479)
- [x] `security-libxml-nonet-729-862`: Add LIBXML_NONET to DOMDocument HTML parsing at lines 729 and 862 (Session: 8307130776701206827)
- [x] Initial Conductor-Jules Setup (Manual Ingestion)
- [x] `project-block-hierarchy`: Include child blocks in data projection
- [x] `include-leading-section-0`: Ensure section 0 is included in data projection
- [x] `tests-section-key`: Verify Section Key Stability
- [x] `test-suite-audit`: Map logic gaps and add field binding coverage
- [x] `security-path-traversal`: Harden and verify path traversal protection
- [x] `perf-serialize-node`: Replace preg_replace with str_replace for root tag removal (resolved #52)
- [x] `refactor-frontmatter-list`: Extract list item parsing to helper method (resolved #53)
- [x] `perf-image-scheme-extraction`: Optimize scheme extraction for large URLs via prefix cleaning
- [x] `perf-dom-audit`: Audit `DOMDocument` performance in large documents (Session: 3342222247799373160)
- [x] `tests-projection-shapes`: Implement unit tests for all `PlainDataProjector` shapes (Session: 5920464444475488240)

## Backlog
- None
