<?php
$iterations = 10000000;
$html = '<root><p>Hello World</p></root>';

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    preg_replace('/<root>/', '', preg_replace('/<\/root>/', '', $html));
}
$end = microtime(true);
echo "nested preg_replace: " . ($end - $start) . " seconds\n";

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    str_replace(['<root>', '</root>'], '', $html);
}
$end = microtime(true);
echo "str_replace array:  " . ($end - $start) . " seconds\n";
