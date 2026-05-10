<?php

use LetMeDown\LetMeDown;
use PHPUnit\Framework\TestCase;

class BindingsTest extends TestCase
{
    public function test_binding_extracts_atomic_value()
    {
        $markdown = <<<MD
<!-- section:main -->
<!-- field:role -->
*admin*
<!-- field:status -->
**active**
<!-- field:no_emphasis -->
plain text
MD;

        $parser = new LetMeDown();
        $content = $parser->loadFromString($markdown);

        $main = $content->section('main');
        $this->assertNotNull($main);

        $role = $main->field('role');
        $this->assertNotNull($role);
        $this->assertSame('binding', $role->type);
        $this->assertSame('admin', $role->data['atomicValue']);
        $this->assertSame('admin', trim($role->text));

        $status = $main->field('status');
        $this->assertNotNull($status);
        $this->assertSame('binding', $status->type);
        $this->assertSame('active', $status->data['atomicValue']);
        $this->assertSame('active', trim($status->text));

        $noEmphasis = $main->field('no_emphasis');
        $this->assertNotNull($noEmphasis);
        $this->assertSame('binding', $noEmphasis->type);
        $this->assertNull($noEmphasis->data['atomicValue']);
        $this->assertSame('plain text', trim($noEmphasis->text));
    }
}
