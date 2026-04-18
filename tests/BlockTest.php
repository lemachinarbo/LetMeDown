<?php

namespace LetMeDown\Tests;

use LetMeDown\Block;
use LetMeDown\ContentElement;
use LetMeDown\ContentElementCollection;
use PHPUnit\Framework\TestCase;

class BlockTest extends TestCase
{
    private function createBlock(array $images = [], array $children = []): Block
    {
        return new Block(
            heading: null,
            level: null,
            content: '',
            html: '',
            text: '',
            markdown: '',
            paragraphs: new ContentElementCollection(),
            images: new ContentElementCollection($images),
            links: new ContentElementCollection(),
            lists: new ContentElementCollection(),
            children: $children
        );
    }

    public function testGetAllImagesReturnsEmptyCollectionWhenNoImages()
    {
        $block = $this->createBlock();
        $images = $block->getAllImages();
        $this->assertCount(0, $images);
    }

    public function testGetAllImagesCollectsDirectImages()
    {
        $img1 = new ContentElement('text1', 'html1');
        $img2 = new ContentElement('text2', 'html2');
        $block = $this->createBlock([$img1, $img2]);

        $images = $block->getAllImages();
        $this->assertCount(2, $images);
        $this->assertSame($img1, $images[0]);
        $this->assertSame($img2, $images[1]);
    }

    public function testGetAllImagesCollectsFromChildrenRecursively()
    {
        $img1 = new ContentElement('text1', 'html1');
        $img2 = new ContentElement('text2', 'html2');
        $img3 = new ContentElement('text3', 'html3');

        $child2 = $this->createBlock([$img3]);
        $child1 = $this->createBlock([$img2], [$child2]);
        $parent = $this->createBlock([$img1], [$child1]);

        $images = $parent->getAllImages();
        $this->assertCount(3, $images);
        $this->assertSame($img1, $images[0]);
        $this->assertSame($img2, $images[1]);
        $this->assertSame($img3, $images[2]);
    }

    public function testGetAllImagesDeduplicatesSameObject()
    {
        $img = new ContentElement('text1', 'html1');

        // Same image object is present in both parent and child
        $child = $this->createBlock([$img]);
        $parent = $this->createBlock([$img], [$child]);

        $images = $parent->getAllImages();
        $this->assertCount(1, $images, 'Should deduplicate the exact same object instance');
        $this->assertSame($img, $images[0]);
    }

    public function testGetAllImagesKeepsDifferentObjectsWithSameContent()
    {
        // Two different objects, but with the same internal content
        $img1 = new ContentElement('text1', 'html1');
        $img2 = new ContentElement('text1', 'html1');

        $child = $this->createBlock([$img2]);
        $parent = $this->createBlock([$img1], [$child]);

        $images = $parent->getAllImages();
        $this->assertCount(2, $images, 'Should not deduplicate different instances even if content is identical');
        $this->assertSame($img1, $images[0]);
        $this->assertSame($img2, $images[1]);
    }
}
