<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class DataProjectionTest extends TestCase
{
    public function test_list_field_projection_uses_named_keys()
    {
        $md = <<<'MD'
<!-- section:body -->

<!-- links -->
- [Modular growing setups](/modular)
- [Tools that fit your city space](/tools)
- [Everything tracked and measurable](/tracking)
- [Systems that scale without chaos](/scaling)
MD;

        $parser = new LetMeDown(__DIR__ . '/fixtures');
        $content = $parser->loadFromString($md);
        $links = $content->body->links->data();

        $this->assertSame('list', $links['type']);
        $this->assertArrayHasKey('html', $links);
        $this->assertArrayHasKey('text', $links);
        $this->assertArrayHasKey('markdown', $links);
        $this->assertArrayHasKey('items', $links);
        $this->assertArrayHasKey('key', $links);
        $this->assertSame('links', $links['key']);
        $this->assertCount(4, $links['items']);
        $this->assertSame('/modular', $links['items'][0]['links'][0]['href']);
    }

    public function test_images_field_projection_keeps_src_and_alt()
    {
        $md = <<<'MD'
<!-- section:body -->

<!-- images -->
![Greenhouse](greenhouse.jpg)
![Redhouse](redhouse.jpg)
MD;

        $parser = new LetMeDown(__DIR__ . '/fixtures');
        $content = $parser->loadFromString($md);
        $images = $content->body->images->data();

        $this->assertSame('images', $images['type']);
        $this->assertArrayHasKey('items', $images);
        $this->assertCount(2, $images['items']);
        $this->assertSame('greenhouse.jpg', $images['items'][0]['src']);
        $this->assertSame('Greenhouse', $images['items'][0]['alt']);
        $this->assertSame('redhouse.jpg', $images['items'][1]['src']);
        $this->assertSame('Redhouse', $images['items'][1]['alt']);
    }
}
