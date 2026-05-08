<?php

namespace LetMeDown\Tests;

use LetMeDown\FieldContainer;
use LetMeDown\Block;
use LetMeDown\FieldData;
use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class FieldContainerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Ensure the monolithic source file is loaded so sibling classes are declared.
        class_exists(LetMeDown::class);
    }

    private function makeBlock(array $fields = [], array $children = []): Block
    {
        $block = (new \ReflectionClass(Block::class))->newInstanceWithoutConstructor();
        $block->fields = $fields;
        $block->children = $children;
        return $block;
    }

    /** @testdox FieldContainer — fields returns an empty array when there are no blocks */
    public function test_fields_method_returns_empty_array_for_no_blocks()
    {
        $container = new FieldContainer('test', '', '', '', []);
        $this->assertSame([], $container->fields());
    }

    /** @testdox FieldContainer — fields method collects and deduplicates fields across blocks */
    public function test_fields_method_collects_fields()
    {
        $field1 = new FieldData('field1', '', '', '', 'text', []);
        $field2 = new FieldData('field2', '', '', '', 'text', []);

        $block1 = $this->makeBlock(['field1' => $field1]);
        $block2 = $this->makeBlock(['field2' => $field2]);

        $container = new FieldContainer('test', '', '', '', [$block1, $block2]);

        $fields = $container->fields();

        $this->assertCount(2, $fields);
        $this->assertArrayHasKey('field1', $fields);
        $this->assertArrayHasKey('field2', $fields);
        $this->assertSame('field1', $fields['field1']->key);
        $this->assertSame('field2', $fields['field2']->key);
        $this->assertSame($field1, $fields['field1']);
        $this->assertSame($field2, $fields['field2']);
    }

    /** @testdox FieldContainer — fields method collects fields from children */
    public function test_fields_method_collects_fields_from_children()
    {
        $field1 = new FieldData('field1', '', '', '', 'text', []);
        $field2 = new FieldData('field2', '', '', '', 'text', []);
        $field3 = new FieldData('field3', '', '', '', 'text', []);

        $grandChildBlock = $this->makeBlock(['field3' => $field3]);
        $childBlock = $this->makeBlock(['field2' => $field2], [$grandChildBlock]);
        $parentBlock = $this->makeBlock(['field1' => $field1], [$childBlock]);

        $container = new FieldContainer('test', '', '', '', [$parentBlock]);

        $fields = $container->fields();

        $this->assertCount(3, $fields);
        $this->assertArrayHasKey('field1', $fields);
        $this->assertArrayHasKey('field2', $fields);
        $this->assertArrayHasKey('field3', $fields);
    }

    /** @testdox FieldContainer — fields method deduplicates identical keys from siblings but respects the first seen */
    public function test_fields_method_deduplicates()
    {
        $field1 = new FieldData('field1', '', '', '', 'text', []);
        $field1_duplicate = new FieldData('field1', 'diff', '', '', 'text', []);

        $block1 = $this->makeBlock(['field1' => $field1]);
        $block2 = $this->makeBlock(['field1' => $field1_duplicate]);

        $container = new FieldContainer('test', '', '', '', [$block1, $block2]);

        $fields = $container->fields();

        $this->assertCount(1, $fields);
        $this->assertArrayHasKey('field1', $fields);
        $this->assertSame($field1, $fields['field1']);
    }

    /** @testdox FieldContainer — fields method deduplicates identical keys from children recursively */
    public function test_fields_method_deduplicates_recursively()
    {
        $field1 = new FieldData('field1', 'parent', '', '', 'text', []);
        $field1_duplicate = new FieldData('field1', 'child', '', '', 'text', []);

        $childBlock = $this->makeBlock(['field1' => $field1_duplicate]);
        $parentBlock = $this->makeBlock(['field1' => $field1], [$childBlock]);

        $container = new FieldContainer('test', '', '', '', [$parentBlock]);

        $fields = $container->fields();

        $this->assertCount(1, $fields);
        $this->assertArrayHasKey('field1', $fields);
        $this->assertSame($field1, $fields['field1']);
        $this->assertSame('parent', $fields['field1']->markdown);
    }
}
