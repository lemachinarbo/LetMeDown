<?php
namespace LetMeDown\Tests;

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class SecurityXssTest extends TestCase
{
    /**
     * @testdox Ensure javascript and vbscript URIs are stripped from links in FieldData
     */
    public function test_unsafe_uris_are_stripped_from_links()
    {
        $markdown = <<<MD
<!-- links -->
[Normal Link](https://google.com)
[XSS Link](javascript:alert('xss'))
[Safe Link](/some/path)
[Vbscript Link](vbscript:alert(1))
[Mailto Link](mailto:test@example.com)
[Tab XSS Link](\tjavascript:alert('xss'))
[Space XSS Link]( javascript:alert('xss'))
[Newline XSS Link](\r\njavascript:alert('xss'))
[Inner Space XSS Link]( java\nscript:alert('xss'))
MD;

        $parser = new LetMeDown();
        $content = $parser->loadFromString($markdown);

        $links = $content->section(0)->field('links')->items();

        $this->assertCount(9, $links);

        // Assert safe https links are intact
        $this->assertStringContainsString('href="https://google.com"', $links[0]->html);

        // Assert javascript uri is changed to #
        $this->assertStringContainsString('href="#"', $links[1]->html);
        $this->assertStringNotContainsString('javascript', $links[1]->html);

        // Assert relative links are intact
        $this->assertStringContainsString('href="/some/path"', $links[2]->html);

        // Assert vbscript uri is changed to #
        $this->assertStringContainsString('href="#"', $links[3]->html);
        $this->assertStringNotContainsString('vbscript', $links[3]->html);

        // Assert mailto links are intact
        $this->assertStringContainsString('href="mailto:test@example.com"', $links[4]->html);

        // Assert bypass vectors are changed to #
        $this->assertStringContainsString('href="#"', $links[5]->html);
        $this->assertStringContainsString('href="#"', $links[6]->html);
        $this->assertStringContainsString('href="#"', $links[7]->html);
        $this->assertStringContainsString('href="#"', $links[8]->html);
    }
}
