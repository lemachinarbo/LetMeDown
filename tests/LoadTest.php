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
}
