<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class DataProjectionTest extends TestCase
{
    private LetMeDown $parser;

    protected function setUp(): void
    {
        $this->parser = new LetMeDown(__DIR__ . '/fixtures');
    }

    public function test_content_projection_includes_named_sections_with_keys()
    {
        $md = <<<'MD'
First block in root, anonymous section 0.

<!-- section:hero -->
# Hero Title
<!-- intro -->
Hero intro.

<!-- section:body -->
## Body Title
Some body text.
MD;

        $content = $this->parser->loadFromString($md);
        $data = $content->data();

        // data() on ContentData should return all sections, indexed by name or numeric key
        $this->assertArrayHasKey('0', $data);
        $this->assertArrayHasKey('hero', $data);
        $this->assertArrayHasKey('body', $data);
        $this->assertCount(3, $data);

        $this->assertSame('hero', $data['hero']['key']);
        $this->assertSame('body', $data['body']['key']);
    }

    public function test_section_projection_includes_subsections_fields_and_text()
    {
        $md = <<<'MD'
<!-- section:main -->
# Main Title
Section text content.

<!-- subtitle -->
Subtitle text.

<!-- sub:left -->
## Left Sub
Left text.
<!-- /sub -->
MD;

        $content = $this->parser->loadFromString($md);
        $mainData = $content->section('main')->data();

        $this->assertSame('main', $mainData['key']);
        $this->assertArrayHasKey('html', $mainData);
        $this->assertArrayHasKey('text', $mainData);
        $this->assertArrayHasKey('markdown', $mainData);

        // Fields
        $this->assertArrayHasKey('subtitle', $mainData);
        $this->assertSame('subtitle', $mainData['subtitle']['key']);
        $this->assertSame('Subtitle text.', $mainData['subtitle']['text']);

        // Subsections
        $this->assertArrayHasKey('subsections', $mainData);
        $this->assertArrayHasKey('left', $mainData['subsections']);
        $this->assertSame('left', $mainData['subsections']['left']['key']);
        $this->assertStringContainsString('Left text.', $mainData['subsections']['left']['text']);

        // Subsections are also directly on the section data array
        $this->assertArrayHasKey('left', $mainData);
        $this->assertSame('left', $mainData['left']['key']);
    }

    public function test_subsection_projection_is_similar_to_section()
    {
        $md = <<<'MD'
<!-- section:main -->

<!-- sub:child -->
## Child Heading
Child content.

<!-- childfield -->
Child field content.
<!-- /sub -->
MD;

        $content = $this->parser->loadFromString($md);
        $childData = $content->section('main')->subsection('child')->data();

        $this->assertSame('child', $childData['key']);
        $this->assertArrayHasKey('html', $childData);
        $this->assertArrayHasKey('text', $childData);
        $this->assertArrayHasKey('markdown', $childData);

        $this->assertArrayHasKey('childfield', $childData);
        $this->assertSame('childfield', $childData['childfield']['key']);
        $this->assertSame('Child field content.', $childData['childfield']['text']);
    }

    public function test_field_container_projection_includes_blocks_and_nested_fields()
    {
        // For LetMeDown, `nestedfield` doesn't get extracted well natively via `container->fields()`
        // because the nested field is technically separated. But if we inject it directly
        // we can test the projection. Let's just create a container and manually add a field to it
        // to test the projection side, rather than the parser side, as this test suite focuses
        // on the PlainDataProjector.

        $container = new \LetMeDown\FieldContainer(
            name: 'mycontainer',
            markdown: 'content',
            html: '<p>content</p>',
            text: 'content',
            blocks: [],
            key: 'mycontainer'
        );
        
        $nestedField = new \LetMeDown\FieldData(
            name: 'nestedfield',
            markdown: 'Nested field text.',
            html: '<p>Nested field text.</p>',
            text: 'Nested field text.',
            type: 'paragraph',
            data: [],
            key: 'nestedfield'
        );
        
        // Use reflection to mock the fields array on a Block and assign to Container
        $block = new \LetMeDown\Block(
            heading: null,
            level: null,
            content: '',
            html: '',
            text: '',
            markdown: '',
            paragraphs: new \LetMeDown\ContentElementCollection(),
            images: new \LetMeDown\ContentElementCollection(),
            links: new \LetMeDown\ContentElementCollection(),
            lists: new \LetMeDown\ContentElementCollection(),
            children: [],
            fields: ['nestedfield' => $nestedField]
        );
        
        $reflection = new \ReflectionClass(\LetMeDown\FieldContainer::class);
        $prop = $reflection->getProperty('blocks');
        $prop->setAccessible(true);
        $prop->setValue($container, [$block]);

        $containerData = \LetMeDown\PlainDataProjector::fieldContainer($container);

        $this->assertSame('mycontainer', $containerData['key']);
        $this->assertArrayHasKey('html', $containerData);
        $this->assertArrayHasKey('text', $containerData);
        $this->assertArrayHasKey('markdown', $containerData);

        // Nested fields
        $this->assertArrayHasKey('nestedfield', $containerData);
        $this->assertSame('nestedfield', $containerData['nestedfield']['key']);
        $this->assertSame('Nested field text.', $containerData['nestedfield']['text']);
    }
    
    public function test_block_data_projection_has_correct_structure()
    {
        // Testing private `blockData` via reflection to ensure its specific structural output
        $block = new \LetMeDown\Block(
            heading: new \LetMeDown\HeadingElement('My Heading', '<h2>My Heading</h2>'),
            level: 2,
            content: 'My content',
            html: '<p>My content</p>',
            text: 'My content',
            markdown: 'My content',
            paragraphs: new \LetMeDown\ContentElementCollection(),
            images: new \LetMeDown\ContentElementCollection(),
            links: new \LetMeDown\ContentElementCollection(),
            lists: new \LetMeDown\ContentElementCollection()
        );

        $reflection = new \ReflectionClass(\LetMeDown\PlainDataProjector::class);
        $method = $reflection->getMethod('blockData');
        $method->setAccessible(true);
        $blockData = $method->invoke(null, $block);

        $this->assertArrayHasKey('html', $blockData);
        $this->assertArrayHasKey('text', $blockData);
        $this->assertArrayHasKey('markdown', $blockData);
        $this->assertArrayHasKey('heading', $blockData);
        $this->assertSame(2, $blockData['heading']['level']);
        $this->assertSame('My Heading', $blockData['heading']['text']);
        $this->assertSame('My Heading', $blockData['heading']['html']);
    }

    public function test_link_field_projection()
    {
        $md = <<<'MD'
<!-- section:main -->
<!-- mylink -->
[My Link Text](https://example.com)
MD;

        $content = $this->parser->loadFromString($md);
        $linkData = $content->section('main')->field('mylink')->data();

        $this->assertSame('link', $linkData['type']);
        $this->assertSame('mylink', $linkData['key']);
        $this->assertSame('https://example.com', $linkData['href']);
        $this->assertSame('My Link Text', $linkData['text']);
        $this->assertArrayHasKey('html', $linkData);
        $this->assertArrayHasKey('markdown', $linkData);
    }

    public function test_link_field_projection_neutralizes_unsafe_href_schemes()
    {
        $md = <<<'MD'
<!-- section:main -->
<!-- links -->
[XSS](javascript:alert(1))

<!-- encoded -->
[Encoded](javascript%3Aalert(1))

<!-- mixed -->
[Mixed](JaVaScRiPt:alert(1))
MD;

        $content = $this->parser->loadFromString($md);

        $this->assertSame('#', $content->section('main')->field('links')->data()['href']);
        $this->assertSame('#', $content->section('main')->field('encoded')->data()['href']);
        $this->assertSame('#', $content->section('main')->field('mixed')->data()['href']);
    }

    public function test_binding_field_projection()
    {
        $md = <<<'MD'
<!-- section:main -->
<!-- field:price -->
The price is **$99**.
MD;

        $content = $this->parser->loadFromString($md);
        $bindingData = $content->section('main')->field('price')->data();

        $this->assertSame('binding', $bindingData['type']);
        $this->assertSame('price', $bindingData['key']);
        $this->assertSame('$99', $bindingData['atomicValue']);
        $this->assertSame('The price is **$99**.', $bindingData['markdown']);
    }

    public function test_list_field_projection_uses_named_keys()
    {
        $md = <<<'MD'
<!-- section:body -->

<!-- links -->
- [Modular growing setups](/modular)
- [Tools that fit your city space](/tools)
- [Everything tracked and measurable](/tracking)
- [Systems that scale without chaos](/scaling)
MD;

        $content = $this->parser->loadFromString($md);
    $links = $content->body->field('links')->data();

        $this->assertSame('list', $links['type']);
        $this->assertArrayHasKey('html', $links);
        $this->assertArrayHasKey('text', $links);
        $this->assertArrayHasKey('markdown', $links);
        $this->assertArrayHasKey('items', $links);
        $this->assertArrayHasKey('key', $links);
        $this->assertSame('links', $links['key']);
        $this->assertCount(4, $links['items']);
        $this->assertSame('/modular', $links['items'][0]['links'][0]['href']);
    }

    public function test_images_field_projection_keeps_src_and_alt()
    {
        $md = <<<'MD'
<!-- section:body -->

<!-- images -->
![Greenhouse](greenhouse.jpg)
![Redhouse](redhouse.jpg)
MD;

        $content = $this->parser->loadFromString($md);
    $images = $content->body->field('images')->data();

        $this->assertSame('images', $images['type']);
        $this->assertArrayHasKey('items', $images);
        $this->assertCount(2, $images['items']);
        $this->assertSame('greenhouse.jpg', $images['items'][0]['src']);
        $this->assertSame('Greenhouse', $images['items'][0]['alt']);
        $this->assertSame('redhouse.jpg', $images['items'][1]['src']);
        $this->assertSame('Redhouse', $images['items'][1]['alt']);
    }

    public function test_unstructured_string_field_projection_includes_basic_properties_and_custom_data()
    {
        $field = new \LetMeDown\FieldData(
            name: 'description',
            markdown: 'This is a **description**.',
            html: '<p>This is a <strong>description</strong>.</p>',
            text: 'This is a description.',
            type: 'paragraph',
            data: ['custom' => 'value', 'empty' => '', 'nullval' => null],
            key: 'desc'
        );

        $data = \LetMeDown\PlainDataProjector::fieldData($field);

        $this->assertSame('desc', $data['type']);
        $this->assertSame('desc', $data['key']);
        $this->assertSame('<p>This is a <strong>description</strong>.</p>', $data['html']);
        $this->assertSame('This is a description.', $data['text']);
        $this->assertSame('This is a **description**.', $data['markdown']);
        $this->assertArrayHasKey('custom', $data);
        $this->assertSame('value', $data['custom']);
        $this->assertArrayNotHasKey('empty', $data);
        $this->assertArrayNotHasKey('nullval', $data);
    }
}
