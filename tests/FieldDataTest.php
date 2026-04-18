<?php

use LetMeDown\LetMeDown;
use LetMeDown\ContentElement;
use PHPUnit\Framework\TestCase;

class FieldDataTest extends TestCase
{
    public function test_iterable_field_data_can_be_iterated_over()
    {
        $markdown = <<<MD
<!-- section:main -->
<!-- list -->
- Item 1
- Item 2
- Item 3
MD;
        $parser = new LetMeDown(__DIR__ . '/fixtures');
        $content = $parser->loadFromString($markdown);

        $listField = $content->section('main')->field('list');
        $this->assertNotNull($listField);
        $this->assertSame('list', $listField->type);

        $iterations = 0;
        $items = [];

        foreach ($listField as $item) {
            $iterations++;
            $this->assertInstanceOf(ContentElement::class, $item);
            $items[] = trim($item->text);
        }

        $this->assertSame(3, $iterations);
        $this->assertSame(['Item 1', 'Item 2', 'Item 3'], $items);
    }

    public function test_scalar_field_data_iteration_yields_no_items()
    {
        $markdown = <<<MD
<!-- section:main -->
<!-- title -->
# My Title
MD;
        $parser = new LetMeDown(__DIR__ . '/fixtures');
        $content = $parser->loadFromString($markdown);

        $titleField = $content->section('main')->field('title');
        $this->assertNotNull($titleField);
        $this->assertSame('heading', $titleField->type);

        $iterations = 0;

        foreach ($titleField as $item) {
            $iterations++;
        }

        $this->assertSame(0, $iterations);
    }
}
