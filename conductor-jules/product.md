# Product Context: LetMeDown

LetMeDown is a high-performance markdown parser for PHP that projects markdown content into a structured data model. It is designed to extract sections, subsections, and typed fields (images, lists, links) from markdown source without losing structural semantics.

## Core Value Proposition
- **Structured Extraction**: Turns flat markdown into a hierarchical object model.
- **Marker-Based Parsing**: Uses HTML comment markers (`<!-- section -->`, `<!-- field -->`) to define data boundaries.
- **Zero-Loss Source**: Preserves the original markdown source for editing while providing a projected data API.

## Target Audience
- Developers building content-rich CMSs or static site generators in PHP.
- Applications requiring "Lossless Editing" (edit markdown, get structured data).

## High-Level Architecture
- `LetMeDown`: Main entry point.
- `ContentData`: The top-level result object.
- `Section`: Hierarchical containers for content.
- `Block`: The fundamental unit of heading-plus-content hierarchy.
- `FieldData`: Typed values (text, lists, images).
