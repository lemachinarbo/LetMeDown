<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class LoadTest extends TestCase
{
    public function test_load_throws_exception_when_file_not_found()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Markdown file not found: non-existent-file.md');

        $parser = new LetMeDown();
        $parser->load('non-existent-file.md');
    }

    public function test_load_throws_exception_for_phar_wrapper()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid file path scheme: phar://test.phar/file.md');

        $parser = new LetMeDown();
        $parser->load('phar://test.phar/file.md');
    }

    public function test_load_throws_exception_for_http_wrapper()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid file path scheme: http://example.com/file.md');

        $parser = new LetMeDown();
        $parser->load('http://example.com/file.md');
    }
}
