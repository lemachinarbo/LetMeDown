<?php
require_once 'src/LetMeDown.php';
use LetMeDown\LetMeDown;

if (!class_exists('Parsedown')) {
    class Parsedown {
        public function text($text) { return $text; }
        public function setSafeMode($bool) { return $this; }
    }
}

$reflection = new ReflectionClass(LetMeDown::class);
$serializeNode = $reflection->getMethod('serializeNode');
$serializeNode->setAccessible(true);
$instance = $reflection->newInstanceWithoutConstructor();

$dom = new DOMDocument();
$node = $dom->createElement('root');
$node->appendChild($dom->createElement('p', 'Hello World'));

$html = $serializeNode->invoke($instance, $node);
echo "Result: [" . $html . "]\n";

if ($html === '<p>Hello World</p>') {
    echo "SUCCESS\n";
} else {
    echo "FAILURE\n";
    exit(1);
}
