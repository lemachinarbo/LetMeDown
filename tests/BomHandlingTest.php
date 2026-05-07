<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class BomHandlingTest extends TestCase
{
    private LetMeDown $parser;

    protected function setUp(): void
    {
        $this->parser = new LetMeDown();
    }

    /**
     * @testdox BOM handling — UTF-8 BOM is removed before frontmatter parsing
     */
    public function test_bom_removed_before_frontmatter_parsing()
    {
        $bom = "\xEF\xBB\xBF";
        $markdown = $bom . "---\ntitle: BOM Test\n---\n# Content";

        $content = $this->parser->loadFromString($markdown);

        $this->assertEquals(['title' => 'BOM Test'], $content->getFrontmatter());
        $this->assertStringContainsString('Content', $content->text);
    }

    /**
     * @testdox BOM handling — UTF-8 BOM is removed when no frontmatter is present
     */
    public function test_bom_removed_without_frontmatter()
    {
        $bom = "\xEF\xBB\xBF";
        $markdown = $bom . "# No Frontmatter Here";

        $content = $this->parser->loadFromString($markdown);

        $this->assertNull($content->getFrontmatter());
        $this->assertStringContainsString('No Frontmatter Here', $content->text);
    }

    /**
     * @testdox BOM handling — multiple BOMs (edge case) only first one is removed by current logic
     * Note: Current implementation uses preg_replace('/^\xEF\xBB\xBF/', '', $markdown)
     * which only replaces at the very beginning.
     */
    public function test_only_leading_bom_removed()
    {
        $bom = "\xEF\xBB\xBF";
        $markdown = $bom . $bom . "---\ntitle: Double BOM\n---\nContent";

        $content = $this->parser->loadFromString($markdown);

        // If there's a second BOM, the frontmatter regex ^--- won't match
        // and it will be treated as plain markdown (with the second BOM still there)
        $this->assertNull($content->getFrontmatter());
        $this->assertStringContainsString($bom . "---\ntitle: Double BOM", $content->getMarkdown());
    }

    /**
     * @testdox BOM handling — BOM with leading whitespace is NOT removed (standard behavior)
     */
    public function test_bom_with_leading_whitespace_not_removed()
    {
        $bom = "\xEF\xBB\xBF";
        $markdown = " " . $bom . "---\ntitle: Delayed BOM\n---\nContent";

        $content = $this->parser->loadFromString($markdown);

        $this->assertNull($content->getFrontmatter());
    }
}
