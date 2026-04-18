<?php

namespace Tests;

use LetMeDown\Block;
use LetMeDown\ContentElementCollection;
use PHPUnit\Framework\TestCase;

class BlockTest extends TestCase
{
    /** @testdox Block — getAllLists collects and deduplicates lists across children */
    public function test_get_all_lists_collects_and_deduplicates()
    {
        $block = (new \ReflectionClass(Block::class))->newInstanceWithoutConstructor();
        $child = (new \ReflectionClass(Block::class))->newInstanceWithoutConstructor();

        $list1 = (object)['text' => 'list1'];
        $list2 = (object)['text' => 'list2'];

        $block->lists = new ContentElementCollection([$list1]);
        $block->children = [$child];
        $child->lists = new ContentElementCollection([$list1, $list2]);
        $child->children = [];

        $this->assertCount(2, $block->getAllLists());
    }
}
