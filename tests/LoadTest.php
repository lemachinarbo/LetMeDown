<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class LoadTest extends TestCase
{
    public function test_load_throws_exception_when_file_not_found()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Markdown file not found: non-existent-file.md');

        $parser = new LetMeDown(__DIR__ . '/fixtures');
        $parser->load('non-existent-file.md');
    }

    public function test_load_throws_exception_when_file_not_found_no_base_path()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Markdown file not found: non-existent-file.md');

        $parser = new LetMeDown();
        $parser->load('/some/arbitrary/path/to/non-existent-file.md');
    }

    public function test_load_throws_exception_on_path_traversal()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path traversal detected');

        // We use the current directory as the base path.
        $parser = new LetMeDown(__DIR__ . '/fixtures');
        // We attempt to traverse outside the base path using `../`
        $parser->load(__DIR__ . '/fixtures/../LoadTest.php');
    }

    public function test_load_with_absolute_path_inside_base_path()
    {
        $parser = new LetMeDown(__DIR__ . '/fixtures');

        // Use an absolute path that is inside the base path
        $absolutePath = realpath(__DIR__ . '/fixtures/test-markdown.md');
        $this->assertNotFalse($absolutePath, 'Fixture file must exist');

        $contentData = $parser->load($absolutePath);

        $this->assertInstanceOf(\LetMeDown\ContentData::class, $contentData);
    }
}
