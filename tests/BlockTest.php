<?php

namespace LetMeDown\Tests;

use LetMeDown\Block;
use LetMeDown\ContentElement;
use LetMeDown\ContentElementCollection;
use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class BlockTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Ensure the monolithic source file is loaded so sibling classes are declared.
        class_exists(LetMeDown::class);
    }

    private function makeBlock(array $images = [], array $lists = [], array $children = [], array $links = [], array $paragraphs = []): Block
    {
        $block = (new \ReflectionClass(Block::class))->newInstanceWithoutConstructor();
        $block->images = new ContentElementCollection($images);
        $block->lists = new ContentElementCollection($lists);
        $block->links = new ContentElementCollection($links);
        $block->paragraphs = new ContentElementCollection($paragraphs);
        $block->children = $children;
        return $block;
    }

    /** @testdox Block — getAllLists collects and deduplicates lists across children */
    public function test_get_all_lists_collects_and_deduplicates()
    {
        $list1 = (object)['text' => 'list1'];
        $list2 = (object)['text' => 'list2'];

        $child = $this->makeBlock([], [$list1, $list2]);
        $block = $this->makeBlock([], [$list1], [$child]);

        $this->assertCount(2, $block->getAllLists());
    }

    /** @testdox Block — getAllImages returns empty collection when no images */
    public function test_get_all_images_returns_empty_collection_when_no_images()
    {
        $block = $this->makeBlock();
        $this->assertCount(0, $block->getAllImages());
    }

    /** @testdox Block — getAllImages collects direct images */
    public function test_get_all_images_collects_direct_images()
    {
        $img1 = new ContentElement('text1', 'html1');
        $img2 = new ContentElement('text2', 'html2');
        $block = $this->makeBlock([$img1, $img2]);

        $images = $block->getAllImages();
        $this->assertCount(2, $images);
        $this->assertSame($img1, $images[0]);
        $this->assertSame($img2, $images[1]);
    }

    /** @testdox Block — getAllImages collects from children recursively */
    public function test_get_all_images_collects_from_children_recursively()
    {
        $img1 = new ContentElement('text1', 'html1');
        $img2 = new ContentElement('text2', 'html2');
        $img3 = new ContentElement('text3', 'html3');

        $child2 = $this->makeBlock([$img3]);
        $child1 = $this->makeBlock([$img2], [], [$child2]);
        $parent = $this->makeBlock([$img1], [], [$child1]);

        $images = $parent->getAllImages();
        $this->assertCount(3, $images);
        $this->assertSame($img1, $images[0]);
        $this->assertSame($img2, $images[1]);
        $this->assertSame($img3, $images[2]);
    }

    /** @testdox Block — getAllImages deduplicates the same object instance */
    public function test_get_all_images_deduplicates_same_object()
    {
        $img = new ContentElement('text1', 'html1');

        $child = $this->makeBlock([$img]);
        $parent = $this->makeBlock([$img], [], [$child]);

        $images = $parent->getAllImages();
        $this->assertCount(1, $images, 'Should deduplicate the exact same object instance');
        $this->assertSame($img, $images[0]);
    }

    /** @testdox Block — getAllImages keeps different objects with identical content */
    public function test_get_all_images_keeps_different_objects_with_same_content()
    {
        $img1 = new ContentElement('text1', 'html1');
        $img2 = new ContentElement('text1', 'html1');

        $child = $this->makeBlock([$img2]);
        $parent = $this->makeBlock([$img1], [], [$child]);

        $images = $parent->getAllImages();
        $this->assertCount(2, $images, 'Should not deduplicate different instances even if content is identical');
        $this->assertSame($img1, $images[0]);
        $this->assertSame($img2, $images[1]);
    }

    /** @testdox Block — getAllLinks returns empty collection when no links */
    public function test_get_all_links_returns_empty_collection_when_no_links()
    {
        $block = $this->makeBlock();
        $this->assertCount(0, $block->getAllLinks());
    }

    /** @testdox Block — getAllLinks collects direct links */
    public function test_get_all_links_collects_direct_links()
    {
        $link1 = new ContentElement('text1', 'html1');
        $link2 = new ContentElement('text2', 'html2');
        $block = $this->makeBlock([], [], [], [$link1, $link2]);

        $links = $block->getAllLinks();
        $this->assertCount(2, $links);
        $this->assertSame($link1, $links[0]);
        $this->assertSame($link2, $links[1]);
    }

    /** @testdox Block — getAllLinks collects from children recursively */
    public function test_get_all_links_collects_from_children_recursively()
    {
        $link1 = new ContentElement('text1', 'html1');
        $link2 = new ContentElement('text2', 'html2');
        $link3 = new ContentElement('text3', 'html3');

        $child2 = $this->makeBlock([], [], [], [$link3]);
        $child1 = $this->makeBlock([], [], [$child2], [$link2]);
        $parent = $this->makeBlock([], [], [$child1], [$link1]);

        $links = $parent->getAllLinks();
        $this->assertCount(3, $links);
        $this->assertSame($link1, $links[0]);
        $this->assertSame($link2, $links[1]);
        $this->assertSame($link3, $links[2]);
    }

    /** @testdox Block — getAllLinks preserves duplicates */
    public function test_get_all_links_preserves_duplicates()
    {
        $link = new ContentElement('text1', 'html1');

        $child = $this->makeBlock([], [], [], [$link]);
        $parent = $this->makeBlock([], [], [$child], [$link]);

        $links = $parent->getAllLinks();
        $this->assertCount(2, $links, 'Should not deduplicate the exact same object instance');
        $this->assertSame($link, $links[0]);
        $this->assertSame($link, $links[1]);
    }

    /** @testdox Block — getAllParagraphs returns empty collection when no paragraphs */
    public function test_get_all_paragraphs_returns_empty_collection_when_no_paragraphs()
    {
        $block = $this->makeBlock();
        $this->assertCount(0, $block->getAllParagraphs());
    }

    /** @testdox Block — getAllParagraphs collects direct paragraphs */
    public function test_get_all_paragraphs_collects_direct_paragraphs()
    {
        $para1 = new ContentElement('text1', 'html1');
        $para2 = new ContentElement('text2', 'html2');
        $block = $this->makeBlock([], [], [], [], [$para1, $para2]);

        $paragraphs = $block->getAllParagraphs();
        $this->assertCount(2, $paragraphs);
        $this->assertSame($para1, $paragraphs[0]);
        $this->assertSame($para2, $paragraphs[1]);
    }

    /** @testdox Block — getAllParagraphs collects from children recursively */
    public function test_get_all_paragraphs_collects_from_children_recursively()
    {
        $para1 = new ContentElement('text1', 'html1');
        $para2 = new ContentElement('text2', 'html2');
        $para3 = new ContentElement('text3', 'html3');

        $child2 = $this->makeBlock([], [], [], [], [$para3]);
        $child1 = $this->makeBlock([], [], [$child2], [], [$para2]);
        $parent = $this->makeBlock([], [], [$child1], [], [$para1]);

        $paragraphs = $parent->getAllParagraphs();
        $this->assertCount(3, $paragraphs);
        $this->assertSame($para1, $paragraphs[0]);
        $this->assertSame($para2, $paragraphs[1]);
        $this->assertSame($para3, $paragraphs[2]);
    }

    /** @testdox Block — getAllParagraphs deduplicates the same object instance */
    public function test_get_all_paragraphs_deduplicates_same_object()
    {
        $para = new ContentElement('text1', 'html1');

        $child = $this->makeBlock([], [], [], [], [$para]);
        $parent = $this->makeBlock([], [], [$child], [], [$para]);

        $paragraphs = $parent->getAllParagraphs();
        $this->assertCount(1, $paragraphs, 'Should deduplicate the exact same object instance');
        $this->assertSame($para, $paragraphs[0]);
    }

    /** @testdox Block — getAllParagraphs keeps different objects with identical content */
    public function test_get_all_paragraphs_keeps_different_objects_with_same_content()
    {
        $para1 = new ContentElement('text1', 'html1');
        $para2 = new ContentElement('text1', 'html1');

        $child = $this->makeBlock([], [], [], [], [$para2]);
        $parent = $this->makeBlock([], [], [$child], [], [$para1]);

        $paragraphs = $parent->getAllParagraphs();
        $this->assertCount(2, $paragraphs, 'Should not deduplicate different instances even if content is identical');
        $this->assertSame($para1, $paragraphs[0]);
        $this->assertSame($para2, $paragraphs[1]);
    }
}
