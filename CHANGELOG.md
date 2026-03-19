# Changelog

## v1.2.3
- added `ContentData::data()` for top-level named section projection
- made `data()` recursive and associative across sections, subsections, field containers, and fields
- field containers now serialize as structural nodes with named children and ordered `items`
- iterable fields expose `items`, while scalar fields keep associative field payloads without positional array flattening
- section-like nodes now expose `subsections` instead of legacy subsection `items`
- tightened sparse output so empty string values are omitted from public `data()` payloads

## v1.2.2
- fixed `data()` projection for structured fields so list and image payloads keep predictable named keys

## v1.2.1
- fixed hyphenated section and subsection markers like `<!-- section:feature-grid -->` and `<!-- sub:name-of-section -->`
- made marker name matching consistent across sections, subsections, and fields

## v1.2

- fixed Composer autoload so LetMeDown classes load correctly from one-file source
- added a plain `data()` view for content structure of sections and fields
