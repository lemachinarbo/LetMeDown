<?php
require_once 'src/LetMeDown.php';
use LetMeDown\LetMeDown;

if (!class_exists('Parsedown')) {
    class Parsedown {
        public function text($text) { return $text; }
        public function setSafeMode($bool) { return $this; }
    }
}

$dom = new DOMDocument();
$node = $dom->createElement('root');
$node->appendChild($dom->createElement('p', 'Hello World'));

$html = $node->ownerDocument->saveHTML($node);
echo "HTML for node with ownerDocument: [" . $html . "]\n";

$dom2 = new \DOMDocument();
$dom2->appendChild($dom2->importNode($node, true));
$html2 = $dom2->saveHTML();
echo "HTML for new DOMDocument: [" . $html2 . "]\n";
