# Spec: Section Key Stability

## Problem
Currently, we lack explicit unit tests to ensure that `Section::key` remains stable and correct throughout the lifecycle of a `ContentData` object, especially after multiple data projections.

## Requirements
- Verify that named sections (`<!-- section:name -->`) have the correct string `key`.
- Verify that anonymous sections (`<!-- section -->`) have the correct numeric index as `key`.
- Verify that `key` does not change after calling `$section->data()`.
- Ensure tests are added to a new `tests/SectionTest.php` file.

## Expected Behavior
```php
$content = $parser->loadFromString('<!-- section:hero -->');
assert($content->sections[0]->key === 'hero');

$content = $parser->loadFromString('<!-- section -->');
assert($content->sections[0]->key === 0);
```
