<?php

namespace LetMeDown\Tests;

use PHPUnit\Framework\TestCase;
use LetMeDown\Block;
use LetMeDown\ContentElement;
use LetMeDown\ContentElementCollection;

class BlockTest extends TestCase
{
    public function testGetAllImages()
    {
        $img1 = new ContentElement('img1', '<img src="1.jpg">');
        $img2 = new ContentElement('img2', '<img src="2.jpg">');
        $img3 = new ContentElement('img3', '<img src="3.jpg">');

        $block2 = new Block(
            null, null, '', '', '', '',
            new ContentElementCollection(),
            new ContentElementCollection([$img3]),
            new ContentElementCollection(),
            new ContentElementCollection(),
            []
        );

        $block1 = new Block(
            null, null, '', '', '', '',
            new ContentElementCollection(),
            new ContentElementCollection([$img1, $img2]),
            new ContentElementCollection(),
            new ContentElementCollection(),
            [$block2]
        );

        $allImages = $block1->getAllImages();

        $this->assertInstanceOf(ContentElementCollection::class, $allImages);
        $this->assertCount(3, $allImages);
        $this->assertSame($img1, $allImages[0]);
        $this->assertSame($img2, $allImages[1]);
        $this->assertSame($img3, $allImages[2]);
    }

    public function testGetAllImagesWithDeduplication()
    {
        $img1 = new ContentElement('img1', '<img src="1.jpg">');

        $block2 = new Block(
            null, null, '', '', '', '',
            new ContentElementCollection(),
            new ContentElementCollection([$img1]),
            new ContentElementCollection(),
            new ContentElementCollection(),
            []
        );

        $block1 = new Block(
            null, null, '', '', '', '',
            new ContentElementCollection(),
            new ContentElementCollection([$img1]),
            new ContentElementCollection(),
            new ContentElementCollection(),
            [$block2]
        );

        $allImages = $block1->getAllImages();

        $this->assertCount(1, $allImages);
        $this->assertSame($img1, $allImages[0]);
    }

    public function testGetAllImagesIntentionalDuplicatesNotDeduplicated()
    {
        $img1 = new ContentElement('img1', '<img src="1.jpg">');
        $img2 = new ContentElement('img1', '<img src="1.jpg">');

        $block1 = new Block(
            null, null, '', '', '', '',
            new ContentElementCollection(),
            new ContentElementCollection([$img1, $img2]),
            new ContentElementCollection(),
            new ContentElementCollection(),
            []
        );

        $allImages = $block1->getAllImages();

        $this->assertCount(2, $allImages);
        $this->assertSame($img1, $allImages[0]);
        $this->assertSame($img2, $allImages[1]);
    }
}
