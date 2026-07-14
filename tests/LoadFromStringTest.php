<?php

use LetMeDown\LetMeDown;
use LetMeDown\ContentData;
use PHPUnit\Framework\TestCase;

class LoadFromStringTest extends TestCase
{
    public function test_load_from_string_parses_markdown()
    {
        $markdown = <<<'MD'
# Test Document

This is a test paragraph.

- Item 1
- Item 2
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);

        $this->assertInstanceOf(ContentData::class, $contentData);

        $this->assertStringContainsString('Test Document', $contentData->text);
        $this->assertStringContainsString('This is a test paragraph.', $contentData->text);

        $this->assertStringContainsString('<h1>Test Document</h1>', $contentData->html);
        $this->assertStringContainsString('<p>This is a test paragraph.</p>', $contentData->html);
        $this->assertStringContainsString('<li>Item 1</li>', $contentData->html);

        $this->assertEquals($markdown, $contentData->markdown);
    }

    public function test_fenced_code_comments_are_not_parsed_as_field_markers()
    {
        $markdown = <<<'MD'
```html
<!-- title -->
```
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);

        $this->assertNull($contentData->section(0)->field('title'));
        $this->assertStringContainsString('&lt;!-- title --&gt;', $contentData->section(0)->html);
    }

    public function test_fenced_hash_lines_are_not_parsed_as_structural_headings()
    {
        $markdown = <<<'MD'
```md
# fake
```

# real
Body
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $section = $contentData->section(0);
        $headingTexts = array_map(
            static fn ($heading) => trim($heading->text),
            $section->headings,
        );

        $this->assertContains('real', $headingTexts);
        $this->assertNotContains('fake', $headingTexts);
    }

    public function test_malformed_nested_fields_do_not_leak_parser_markers_into_payloads()
    {
        $markdown = <<<'MD'
<!-- a -->
A
<!-- b -->
B
<!-- /a -->
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $section = $contentData->section(0);

        $fieldA = $section->field('a');
        $fieldB = $section->field('b');

        $this->assertNotNull($fieldA);
        $this->assertNotNull($fieldB);
        $this->assertStringNotContainsString('<!--', $fieldA->markdown);
        $this->assertStringNotContainsString('<!--', $fieldB->markdown);
    }

    public function test_well_formed_nested_fields_do_not_leak_markers()
    {
        $markdown = <<<'MD'
<!-- a... -->
A
<!-- b -->
B
<!-- /b -->
C
<!-- / -->
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $section = $contentData->section(0);

        $fieldA = $section->field('a');
        $fieldB = $section->field('b');

        $this->assertNotNull($fieldA);
        $this->assertNotNull($fieldB);

        $this->assertStringNotContainsString('<!--', $fieldA->markdown);
        $this->assertStringNotContainsString('<!--', $fieldB->markdown);

        // Verify content structures
        $this->assertSame("A\n\nB\n\nC", trim($fieldA->markdown));
        $this->assertSame("B", trim($fieldB->markdown));
    }

    public function test_adjacent_nested_field_openers()
    {
        $markdown = <<<'MD'
<!-- a --><!-- b -->
B
<!-- /a -->
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $section = $contentData->section(0);

        $fieldA = $section->field('a');
        $fieldB = $section->field('b');

        $this->assertNotNull($fieldA);
        $this->assertNotNull($fieldB);
        $this->assertSame("B", trim($fieldA->markdown));
        $this->assertSame("B", trim($fieldB->markdown));
    }

    public function test_duplicate_subsection_names_preserve_first_subsection()
    {
        $markdown = <<<'MD'
<!-- section:main -->

<!-- sub:dup -->
First copy.
<!-- /sub -->

<!-- sub:dup -->
Second copy.
<!-- /sub -->
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $dup = $contentData->section('main')->subsection('dup');

        $this->assertNotNull($dup);
        $this->assertStringContainsString('First copy.', $dup->text);
        $this->assertStringNotContainsString('Second copy.', $dup->text);
    }

    public function test_setext_headings_are_preserved_in_block_structure()
    {
        $markdown = <<<'MD'
Title
=====

Para
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $firstBlock = $contentData->section(0)->blocks[0];

        $this->assertNotNull($firstBlock->heading);
        $this->assertSame('Title', trim($firstBlock->heading->text));
        $this->assertSame(1, $firstBlock->level);
    }

    public function test_compact_field_markers_are_parsed_like_compact_section_markers()
    {
        $markdown = <<<'MD'
<!--section:hero-->
<!--title-->
Hello
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $section = $contentData->section('hero');
        $this->assertNotNull($section);
        $title = $section->field('title');

        $this->assertNotNull($title);
        $this->assertSame('Hello', trim($title->text));
    }

    public function test_fenced_code_invalid_backtick_info_string_is_parsed_as_marker()
    {
        $markdown = <<<'MD'
```php`test
<!-- title -->
```
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);

        // Since the fence is invalid under CommonMark, the marker should be recognized.
        $this->assertNotNull($contentData->section(0)->field('title'));
    }

    public function test_setext_heading_underline_not_reprocessed()
    {
        $markdown = <<<'MD'
Title
=====
-----
MD;
        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $this->assertCount(1, $contentData->section(0)->blocks);
        $this->assertSame('Title', trim($contentData->section(0)->blocks[0]->heading->text));
    }

    public function test_list_item_with_empty_bullet_not_setext_heading()
    {
        $markdown = <<<'MD'
- item 1
-
MD;
        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $this->assertNull($contentData->section(0)->blocks[0]->heading);
    }

    public function test_multiline_setext_headings()
    {
        $markdown = <<<'MD'
This is a
multi-line heading
==================

Para
MD;
        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $firstBlock = $contentData->section(0)->blocks[0];

        $this->assertNotNull($firstBlock->heading);
        $this->assertSame("This is a\nmulti-line heading", trim($firstBlock->heading->text));
        $this->assertSame("This is a\nmulti-line heading\n==================\n\nPara", trim($firstBlock->markdown));
    }

    public function test_setext_heading_in_blockquote()
    {
        $markdown = <<<'MD'
> Title
> =====
MD;
        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);
        $headings = $contentData->section(0)->headings;

        $this->assertCount(1, $headings);
        $this->assertSame('Title', trim($headings[0]->text));
    }

    public function test_unclosed_comment_with_many_spaces_does_not_cause_redos()
    {
        $spaces = str_repeat(' ', 10000);
        $markdown = "<!--" . $spaces . "section:hero";
        
        $parser = new LetMeDown();
        $startTime = microtime(true);
        $parser->loadFromString($markdown);
        $duration = microtime(true) - $startTime;
        
        // It should complete in less than 100 milliseconds
        $this->assertLessThan(0.1, $duration);
    }

    public function test_clean_markdown_strips_markers_correctly()
    {
        $markdown = <<<'MD'
<!-- section:hero -->
# Hero Title

<!-- title -->
This is the title.
<!-- /title -->

Some body text.

<!-- sub:features -->
- Feature 1
- Feature 2
<!-- /sub -->

```html
<!-- title -->
<!-- code example comment -->
```
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);

        $expectedClean = <<<'MD'
# Hero Title

This is the title.

Some body text.

- Feature 1
- Feature 2

```html
<!-- title -->
<!-- code example comment -->
```
MD;

        $this->assertSame(trim($expectedClean), $contentData->cleanMarkdown);

        // Also check that the hero section's cleanMarkdown is correct
        $hero = $contentData->section('hero');
        $this->assertNotNull($hero);
        $expectedHeroClean = <<<'MD'
# Hero Title

This is the title.

Some body text.

```html
<!-- title -->
<!-- code example comment -->
```
MD;
        $this->assertSame(trim($expectedHeroClean), $hero->cleanMarkdown);

        // Verify that the subsection itself has the correct cleanMarkdown
        $features = $hero->subsection('features');
        $this->assertNotNull($features);
        $expectedFeaturesClean = <<<'MD'
- Feature 1
- Feature 2
MD;
        $this->assertSame(trim($expectedFeaturesClean), $features->cleanMarkdown);
    }

    public function test_get_clean_markdown_with_resolvers()
    {
        $markdown = <<<'MD'
# Hello
![some image](photo.jpg)
Check [our services](services) page.
MD;

        $parser = new LetMeDown();
        $contentData = $parser->loadFromString($markdown);

        $imageResolver = fn($ref, $alt) => "https://example.com/assets/" . $ref;
        $linkResolver = fn($ref, $text) => "https://example.com/" . $ref . ".html";

        $resolved = $contentData->getCleanMarkdown($imageResolver, $linkResolver);

        $expected = <<<'MD'
# Hello
![some image](https://example.com/assets/photo.jpg)
Check [our services](https://example.com/services.html) page.
MD;

        $this->assertSame(trim($expected), $resolved);
    }
}

