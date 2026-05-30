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

    public function test_field_named_links_does_not_shadow_structural_links_collection()
    {
        $markdown = <<<'MD'
<!-- section:main -->
<!-- links -->
[Field Link](https://field.example.test)

[Section Link](https://section.example.test)
MD;

        $content = $this->getParser()->loadFromString($markdown);
        $section = $content->section('main');
        $linksField = $section->field('links');
        $linkTexts = array_map(
            static fn ($link) => trim($link->text),
            $section->links->getArrayCopy(),
        );

        $this->assertInstanceOf(\LetMeDown\FieldData::class, $linksField);
        $this->assertSame('https://field.example.test', $linksField->data()['href']);
        $this->assertInstanceOf(\LetMeDown\ContentElementCollection::class, $section->links);
        $this->assertCount(2, $section->links);
        $this->assertContains('Field Link', $linkTexts);
        $this->assertContains('Section Link', $linkTexts);
    }

    public function test_section_named_links_does_not_shadow_content_data_links()
    {
        $markdown = <<<'MD'
<!-- section:links -->
[Link 1](https://example.com)
MD;
        $content = $this->getParser()->loadFromString($markdown);
        // $content->links should be the global links collection
        $this->assertInstanceOf(\LetMeDown\ContentElementCollection::class, $content->links);
        $this->assertCount(1, $content->links);
        
        // $content->section('links') should access the section
        $this->assertInstanceOf(\LetMeDown\Section::class, $content->section('links'));
    }

    public function test_block_fields_do_not_shadow_reserved_properties()
    {
        $markdown = <<<'MD'
<!-- section:main -->
# Heading
<!-- allLinks -->
[Link](https://example.com)
MD;
        $content = $this->getParser()->loadFromString($markdown);
        $block = $content->section('main')->blocks[0];
        
        // $block->allLinks should return structural collection
        $this->assertInstanceOf(\LetMeDown\ContentElementCollection::class, $block->allLinks);
        $this->assertCount(1, $block->allLinks);
        
        // $block->field('allLinks') should access the field
        $this->assertInstanceOf(\LetMeDown\FieldData::class, $block->field('allLinks'));
        
        // isset checks
        $this->assertTrue(isset($block->allLinks));
        $this->assertTrue(isset($block->headings));
    }

    private function getParser(): LetMeDown
    {
        return new LetMeDown(sys_get_temp_dir());
    }
}
