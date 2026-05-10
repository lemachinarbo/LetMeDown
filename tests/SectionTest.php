<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class SectionTest extends TestCase
{
    public function test_multi_section_document_keys(): void
    {
        $md = <<<MD
First anonymous section content.

<!-- section:hero -->
Hero section content.

<!-- section -->
Second anonymous section.
MD;
        $tmp = sys_get_temp_dir() . '/letmedown_test_multisection_' . uniqid() . '.md';
        file_put_contents($tmp, $md);

        $parser = new LetMeDown(sys_get_temp_dir());
        $content = $parser->load(basename($tmp));

        $sections = $content->sections;

        $this->assertCount(3, $sections);

        $this->assertSame('0', $sections[0]->key);
        $this->assertSame('hero', $sections[1]->key);
        $this->assertSame('2', $sections[2]->key);

        unlink($tmp);
    }
}
