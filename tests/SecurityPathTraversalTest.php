<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class SecurityPathTraversalTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = realpath(__DIR__ . '/fixtures');
    }

    /**
     * @testdox load() should throw RuntimeException when attempting relative path traversal
     */
    public function test_load_throws_exception_on_relative_path_traversal()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path traversal detected');

        $parser = new LetMeDown($this->basePath);
        // /app/tests/LoadTest.php exists
        $parser->load('../LoadTest.php');
    }

    /**
     * @testdox load() should throw RuntimeException when attempting absolute path traversal outside base path
     */
    public function test_load_throws_exception_on_absolute_path_traversal()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path traversal detected');

        $parser = new LetMeDown($this->basePath);
        // /etc/passwd exists on Linux systems
        $parser->load('/etc/passwd');
    }

    /**
     * @testdox load() should throw RuntimeException when attempting to access a sibling directory that starts with the same name
     */
    public function test_load_throws_exception_on_sibling_directory_traversal()
    {
        $tempDir = sys_get_temp_dir();
        $base = $tempDir . DIRECTORY_SEPARATOR . 'letmedown_test_' . uniqid();
        mkdir($base);
        
        $fixtures = $base . DIRECTORY_SEPARATOR . 'fixtures';
        mkdir($fixtures);
        
        $malicious = $base . DIRECTORY_SEPARATOR . 'fixtures-malicious';
        mkdir($malicious);
        
        $targetFile = $malicious . DIRECTORY_SEPARATOR . 'secret.md';
        file_put_contents($targetFile, 'secret content');
        
        $realFixtures = realpath($fixtures);
        $parser = new LetMeDown($realFixtures);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path traversal detected');

        try {
            $parser->load('..' . DIRECTORY_SEPARATOR . 'fixtures-malicious' . DIRECTORY_SEPARATOR . 'secret.md');
        } finally {
            unlink($targetFile);
            rmdir($malicious);
            rmdir($fixtures);
            rmdir($base);
        }
    }

    /**
     * @testdox load() should allow valid paths that use '..' but remain within the base path
     */
    public function test_load_allows_valid_paths_with_dots()
    {
        $tempDir = sys_get_temp_dir();
        $base = $tempDir . DIRECTORY_SEPARATOR . 'letmedown_valid_' . uniqid();
        mkdir($base);
        
        $subdir = $base . DIRECTORY_SEPARATOR . 'subdir';
        mkdir($subdir);
        
        $targetFile = $base . DIRECTORY_SEPARATOR . 'test.md';
        file_put_contents($targetFile, 'content');
        
        $parser = new LetMeDown(realpath($base));

        try {
            $contentData = $parser->load('subdir' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'test.md');
            $this->assertInstanceOf(\LetMeDown\ContentData::class, $contentData);
        } finally {
            unlink($targetFile);
            rmdir($subdir);
            rmdir($base);
        }
    }

    /**
     * @testdox load() should throw RuntimeException when file does not exist even if it is within base path
     */
    public function test_load_throws_exception_on_non_existent_file_within_base_path()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Markdown file not found');

        $parser = new LetMeDown($this->basePath);
        $parser->load('non-existent.md');
    }

    /**
     * @testdox Constructor should throw RuntimeException for invalid base path
     */
    public function test_constructor_throws_exception_on_invalid_base_path()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid base path');

        new LetMeDown(__DIR__ . DIRECTORY_SEPARATOR . 'non-existent-base-path');
    }
}
