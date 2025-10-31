# ~~Dont~~ LetMeDown:  a Markdown parser that probably will.

Parsedown → turns Markdown into HTML.  
LetMeDown → turns Markdown into a structured content tree (sections, fields, blocks, etc).

Parsedown makes Markdown pretty.  
LetMeDown makes Markdown complicated.


This guide explains how to use the `LetMeDown` to parse structured markdown and access its content.

## Quick Start

```php
$processor = new LetMeDown();
$content = $processor->load('path/to/your/markdown.md');
```

## Markdown Format

The processor recognizes special HTML comments to structure the content.

### Sections and Fields

- **Sections**: Divide your document into large parts using `<!-- section:name -->` or `<!-- section -->`.
- **Fields**: Tag specific elements within a section using `<!-- fieldname -->`.

Here is a minimal example:

```markdown
<!-- section:intro --> // A named 'section'

<!-- title --> // A 'field' for the heading element
# Welcome to our page // A 'block' starts with a heading, this is also a heading 'element'

<!-- summary --> // A 'field' for the paragraph element
This is a summary of the page content. // This is a paragraph 'element'
```

## Accessing Content

There are two main ways to access content: semantic (by name) and positional (by order).

### 1. Semantic Access (Recommended)

Access content by the names you provided for sections and fields. This method is stable and easy to read.

```php
// Access a section by name, then a field by its tag
$title = $content->sections['intro']->field('title')->text;

// Get the 'src' from an image field
$imageUrl = $content->sections['intro']->field('image')->src;

// Get items from a multi-item field (e.g., a list of links or images)
$links = $content->sections['intro']->field('ctas')->items;
foreach ($links as $link) {
  echo $link->href;
}
```

### 2. Positional Access

Access content by its numerical order. This is useful for looping but can be brittle if the markdown structure changes.

Here's a quick cheatsheet:

```php
$content->sections[0];                // First section in the document.
$content->sections[0]->blocks[0];     // First block in the first section.
$content->sections[0]->blocks[0]->children[0]; // First child of the first block.
$content->sections[0]->images[0];     // First image found in the first section.
$content->sections[0]->paragraphs;     // All paragraphs of first section.
```

You can also get global collections of all elements across the entire document:

```php
$content->images;     // Array of all images.
$content->links;      // Array of all links.
$content->headings;   // Array of all headings.
$content->lists;      // Array of all lists.
$content->paragraphs; // Array of all paragraphs.
```

> **Note on Collections:** Properties that return a list of elements (like `$section->paragraphs` or `$block->images`) now return a special `ContentElementCollection`. You can still loop through it like a normal array, but you can also access `->html` or `->text` on the collection itself to get the combined content of all its items.

## The Field Object

When you access a field with `$section->field('name')`, you get a `FieldData` object with the following properties:

-   `->text`: The plain text content.
-   `->html`: The rendered HTML content.
-   `->markdown`: The original markdown source.
-   `->type`: The auto-detected type (`image`, `link`, `list`, `heading`, `text`).
-   `->src`, `->alt`: For `image` type fields.
-   `->href`: For `link` type fields.
-   `->items`: For fields with multiple items (like a list of images or links) or for `list` type fields.

```php
$imageField = $content->sections['hero']->field('image');

echo $imageField->src; // "path/to/image.jpg"
echo $imageField->alt; // "Hero Image"
```

### Handling List Fields

When a field is a `list`, each item in the `data` array is a structured object containing the full `html` and `text` of the `<li>` element, plus `links` and `images` arrays for any media inside.

**Example:**

Given this Markdown:

```markdown
<!-- my_list -->
- Just some text.
- A link to [Google](https://google.com).
- An image: ![alt text](image.jpg)
- Both: [link](a) and ![img](b)
```

The `data` for the `my_list` field will be structured like this (omitting `html` for brevity):

```json
[
  {
    "text": "Just some text.",
    "links": [],
    "images": []
  },
  {
    "text": "A link to Google.",
    "links": [
      { "text": "Google", "href": "https://google.com" }
    ],
    "images": []
  },
  {
    "text": "An image:",
    "links": [],
    "images": [
      { "src": "image.jpg", "alt": "alt text" }
    ]
  },
  {
    "text": "Both: link and",
    "links": [
      { "text": "link", "href": "a" }
    ],
    "images": [
      { "src": "b", "alt": "img" }
    ]
  }
]
```


Thats it.



> And for the first time that I really done it  
> Oh, I done it, and it parsed it good.