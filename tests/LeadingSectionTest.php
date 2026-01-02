<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class LeadingSectionTest extends TestCase
{
    public function test_leading_content_becomes_first_section()
    {
        $md = <<<MD
---
title: The Urban Farm Studio.
---

Intro text for the section.

<!-- section:bye -->
# Tagged section here
Short text.
MD;

        $tmp = sys_get_temp_dir() . '/letmedown_test_' . uniqid() . '.md';
        file_put_contents($tmp, $md);

        $parser = new LetMeDown();
        $content = $parser->load($tmp);

        // The leading intro should be the first section
        $this->assertSame('Intro text for the section.', trim($content->section(0)->text));

        unlink($tmp);
    }

    public function test_marker_at_start_has_no_leading_section()
    {
        $md = "<!-- section:hero -->\n# Heading\nSome text.";
        $tmp = sys_get_temp_dir() . '/letmedown_test_' . uniqid() . '.md';
        file_put_contents($tmp, $md);

        $parser = new LetMeDown();
        $content = $parser->load($tmp);

        // First section should be the tagged section
        $this->assertSame("Heading\n\nSome text.", trim($content->section(0)->text));

        // No unnamed leading section should be present
        $this->assertNull($content->section(''));

        unlink($tmp);
    }
}
