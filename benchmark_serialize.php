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

$iterations = 1000000; // Increased iterations for more stable results

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $serializeNode->invoke($instance, $node);
}
$end = microtime(true);
echo "Result: " . ($end - $start) . " seconds for " . $iterations . " iterations\n";
