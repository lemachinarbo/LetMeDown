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
        $this->assertStringNotContainsString('<!--', $fieldA->markdown);
        $this->assertStringNotContainsString('<!--', $fieldB?->markdown ?? '');
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
}
