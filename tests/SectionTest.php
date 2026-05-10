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

    public function test_named_section_key_is_correct()
    {
        $markdown = "<!-- section:my_section -->\n# Heading";
        $content = $this->getParser()->loadFromString($markdown);
        $this->assertEquals('my_section', $content->sections[0]->key);
    }

    public function test_section_key_is_stable_after_data_projection()
    {
        $markdown = "<!-- section:my_section -->\n# Heading";
        $content = $this->getParser()->loadFromString($markdown);
        $section = $content->sections[0];
        
        $data = $content->data();
        $this->assertEquals('my_section', $data['my_section']['key']);
        $this->assertEquals('my_section', $section->key);
    }

    private function getParser(): LetMeDown
    {
        return new LetMeDown(sys_get_temp_dir());
    }
}
