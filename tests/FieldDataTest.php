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
        $parser = new LetMeDown();
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
        $parser = new LetMeDown();
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

    public function test_items_method_caches_collection()
    {
        $markdown = <<<MD
<!-- section:main -->
<!-- list -->
- Item 1
MD;
        $parser = new LetMeDown();
        $content = $parser->loadFromString($markdown);
        $listField = $content->section('main')->field('list');

        $items1 = $listField->items();
        $items2 = $listField->items();

        $this->assertSame($items1, $items2);
    }

    public function test_items_collection_for_list()
    {
        $markdown = <<<MD
<!-- section:main -->
<!-- list -->
- First item
- Second item
MD;
        $parser = new LetMeDown();
        $content = $parser->loadFromString($markdown);
        $listField = $content->section('main')->field('list');
        $items = $listField->items();

        $this->assertCount(2, $items);
        $this->assertSame('First item', trim($items[0]->text));
        $this->assertSame('Second item', trim($items[1]->text));
    }

    public function test_items_collection_for_images()
    {
        $markdown = <<<MD
<!-- section:main -->
<!-- images -->
![Alt 1](src1.jpg)
![Alt 2](src2.png)
MD;
        $parser = new LetMeDown();
        $content = $parser->loadFromString($markdown);
        $imagesField = $content->section('main')->field('images');
        $items = $imagesField->items();

        $this->assertCount(2, $items);
        $this->assertSame('Alt 1', trim($items[0]->text));
        $this->assertSame('<img src="src1.jpg" alt="Alt 1">', trim($items[0]->html));
        $this->assertSame('Alt 2', trim($items[1]->text));
        $this->assertSame('<img src="src2.png" alt="Alt 2">', trim($items[1]->html));
    }

    public function test_items_collection_for_links()
    {
        $markdown = <<<MD
<!-- section:main -->
<!-- links -->
[Link 1](href1.com)
[Link 2](href2.com)
MD;
        $parser = new LetMeDown();
        $content = $parser->loadFromString($markdown);
        $linksField = $content->section('main')->field('links');
        $items = $linksField->items();

        $this->assertCount(2, $items);
        $this->assertSame('Link 1', trim($items[0]->text));
        $this->assertSame('<a href="href1.com">Link 1</a>', trim($items[0]->html));
        $this->assertSame('Link 2', trim($items[1]->text));
        $this->assertSame('<a href="href2.com">Link 2</a>', trim($items[1]->html));
    }
}
