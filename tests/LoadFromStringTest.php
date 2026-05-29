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
}
