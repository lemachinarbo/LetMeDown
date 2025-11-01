<?php

namespace LetMeDown;

use Parsedown;

/**
 * LetMeDown: Handles loading and parsing Markdown content files
 *
 * Provides flexible parsing for different content structures, with support for
 * extracting headings, paragraphs, images and lists from natural Markdown.
 */
class LetMeDown
{
  private Parsedown $parsedown;

  public function __construct()
  {
    $this->parsedown = new Parsedown();
  }

  /**
   * Load and parse a Markdown file with default extraction rules
   *
   * @param string $filePath Full path to the markdown file
   * @return ContentData Standardized content structure with defaults
   */
  public function load(string $filePath): ContentData
  {
    if (!file_exists($filePath)) {
      throw new \RuntimeException("Markdown file not found: {$filePath}");
    }

    $markdown = file_get_contents($filePath);

    // Split markdown into sections by named or unnamed section markers
    $sections = [];
    $currentIndex = 0;

    // Match both <!-- section --> (unnamed) and <!-- section:name --> (named)
    preg_match_all(
      '/<!-- section(?::(\w+))? -->/m',
      $markdown,
      $matches,
      PREG_OFFSET_CAPTURE,
    );

    if (empty($matches[0])) {
      // No section markers found, treat entire content as one section
      $sections[] = ['name' => null, 'content' => $markdown];
    } else {
      foreach ($matches[0] as $i => $match) {
        // Get the captured group (section name) - use null if not captured
        $sectionName = !empty($matches[1][$i][0]) ? $matches[1][$i][0] : null;
        $startPos = $match[1] + strlen($match[0]);

        // Find the end position (start of next section or end of file)
        $endPos = isset($matches[0][$i + 1])
          ? $matches[0][$i + 1][1]
          : strlen($markdown);

        $content = substr($markdown, $startPos, $endPos - $startPos);
        $sections[] = ['name' => $sectionName, 'content' => trim($content)];
      }
    }

    return $this->extractDefaults($sections);
  }

  /**
   * Parse field markers within a section's markdown content
   *
   * Extracts content tagged with <!-- fieldname --> or <!-- fieldname... --> markers
   * Regular fields (<!-- fieldname -->) stop at the first blank line
   * Extended fields (<!-- fieldname... -->) bleed until <!-- / --> or next marker
   * Only keeps the FIRST occurrence of each field name to prevent sub-block
   * fields from overwriting top-level fields with the same name.
   *
   * @param string $markdown Section markdown content
   * @return array Associative array of field names to FieldData objects
   */
  private function parseFieldMarkers(string $markdown): array
  {
    $fields = [];
    $seenFieldNames = [];

    // Match both regular fields and extended fields (with ...)
    // Also match field closers and generic closers
    preg_match_all(
      '/<!-- ([a-zA-Z0-9_-]+)(\.{3})? -->|<!-- (?:\/([a-zA-Z0-9_-]+)|\/) -->/m',
      $markdown,
      $allMatches,
      PREG_OFFSET_CAPTURE,
    );

    if (empty($allMatches[0])) {
      return $fields;
    }

    $openStack = [];
    $fieldRanges = [];

    foreach ($allMatches[0] as $i => $match) {
      $fullMatch = $match[0];
      $position = $match[1];

      // Check if it's a field opener (regular or extended)
      if (!empty($allMatches[1][$i][0])) {
        $fieldName = $allMatches[1][$i][0];
        $isExtended = !empty($allMatches[2][$i][0]); // Has "..." ?

        $openStack[] = [
          'name' => $fieldName,
          'start' => $position + strlen($fullMatch),
          'extended' => $isExtended,
          'index' => $i,
        ];
      }
      // Check if it's a closer (<!-- /fieldname --> or <!-- / -->)
      elseif (
        preg_match(
          '/<!-- (?:\/([a-zA-Z0-9_-]+)|\/) -->/',
          $fullMatch,
          $closerMatch,
        )
      ) {
        if (!empty($openStack)) {
          // Check if it's a named closer (<!-- /fieldname -->)
          if (!empty($allMatches[3][$i][0])) {
            $closerName = $allMatches[3][$i][0];
            // Find and close the specific field with this name
            for (
              $stackIdx = count($openStack) - 1;
              $stackIdx >= 0;
              $stackIdx--
            ) {
              if ($openStack[$stackIdx]['name'] === $closerName) {
                $opener = array_splice($openStack, $stackIdx, 1)[0];
                $fieldRanges[] = [
                  'name' => $opener['name'],
                  'start' => $opener['start'],
                  'end' => $position,
                  'extended' => $opener['extended'],
                ];
                break;
              }
            }
          } else {
            // Generic closer <!-- / --> - close most recent
            $opener = array_pop($openStack);
            $fieldRanges[] = [
              'name' => $opener['name'],
              'start' => $opener['start'],
              'end' => $position,
              'extended' => $opener['extended'],
            ];
          }
        }
      }
    }

    // Handle unclosed fields
    while (!empty($openStack)) {
      $opener = array_pop($openStack);
      $nextMarkerPos = null;

      // Find the next field marker after this one
      foreach ($allMatches[0] as $j => $match) {
        if (
          $j > $opener['index'] &&
          !empty($allMatches[1][$j][0]) &&
          $match[1] > $opener['start']
        ) {
          $nextMarkerPos = $match[1];
          break;
        }
      }

      $fieldRanges[] = [
        'name' => $opener['name'],
        'start' => $opener['start'],
        'end' => $nextMarkerPos ?? strlen($markdown),
        'extended' => $opener['extended'],
      ];
    }

    // Extract content for each field range
    foreach ($fieldRanges as $range) {
      if (isset($seenFieldNames[$range['name']])) {
        continue; // Skip duplicate field names
      }

      $fieldContent = trim(
        substr($markdown, $range['start'], $range['end'] - $range['start']),
      );

      if (empty($fieldContent)) {
        continue;
      }

      // For regular (non-extended) fields, limit to first block
      if (!$range['extended']) {
        $fieldParts = preg_split(
          '/(?:\r\n|\n)\s*(?:\r\n|\n)/',
          $fieldContent,
          2,
        );
        $fieldContent = $fieldParts[0];
      }

      $fieldHtml = $this->parsedown->text($fieldContent);
      $fieldText = trim(strip_tags($fieldHtml));
      $fieldData = $this->extractFieldData(
        $fieldContent,
        $fieldHtml,
        $fieldText,
      );

      $fields[$range['name']] = new FieldData(
        name: $range['name'],
        markdown: $fieldContent,
        html: trim($fieldHtml),
        text: $fieldText,
        type: $fieldData['type'],
        data: $fieldData['data'],
      );

      $seenFieldNames[$range['name']] = true;
    }

    return $fields;
  }

  /**
   * Extract structured data from field content based on type detection
   *
   * Uses DOM queries for HTML extraction (consistent with extractBlockContent)
   * Falls back to regex for markdown-only detection
   *
   * @param string $markdown Raw markdown content
   * @param string $html Parsed HTML content
   * @param string $text Plain text content
   * @return array Array with 'type' and 'data' keys
   */
  private function extractFieldData(
    string $markdown,
    string $html,
    string $text,
  ): array {
    // Parse HTML into DOM for consistent extraction
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8"?><root>' . $html . '</root>');
    libxml_use_internal_errors(false);

    $xpath = new \DOMXPath($dom);

    // Detect headings from markdown (no HTML representation)
    if (preg_match('/^#+\s+/m', $markdown)) {
      return [
        'type' => 'heading',
        'data' => [],
      ];
    }

    // Extract lists via DOM (both ul and ol)
    $listNodes = $xpath->query('//ul | //ol');
    if ($listNodes->length > 0) {
      $items = [];
      foreach ($listNodes as $listNode) {
        /** @var \DOMElement $listNode */
        $listItems = $xpath->query('.//li', $listNode);
        foreach ($listItems as $liNode) {
          /** @var \DOMElement $liNode */

          $liHtml = $this->serializeNode($liNode);
          $liText = trim(strip_tags($liHtml));

          $links = [];
          $linkNodes = $xpath->query('.//a[@href]', $liNode);
          foreach ($linkNodes as $linkNode) {
            /** @var \DOMElement $linkNode */
            $links[] = [
              'text' => trim($linkNode->textContent ?? ''),
              'href' => $linkNode->getAttribute('href') ?? '',
            ];
          }

          $images = [];
          $imageNodes = $xpath->query('.//img', $liNode);
          foreach ($imageNodes as $imageNode) {
            /** @var \DOMElement $imageNode */
            $images[] = [
              'src' => $imageNode->getAttribute('src') ?? '',
              'alt' => $imageNode->getAttribute('alt') ?? '',
            ];
          }

          $items[] = [
            'html' => $liHtml,
            'text' => $liText,
            'links' => $links,
            'images' => $images,
          ];
        }
      }
      return [
        'type' => 'list',
        'data' => $items,
      ];
    }

    // Extract images via DOM (more robust than regex)
    $imgNodes = $xpath->query('//img');
    if ($imgNodes->length > 0) {
      $images = [];
      foreach ($imgNodes as $img) {
        /** @var \DOMElement $img */
        $images[] = [
          'src' => $img->getAttribute('src') ?? '',
          'alt' => $img->getAttribute('alt') ?? '',
        ];
      }

      if (count($images) > 1) {
        return [
          'type' => 'images',
          'data' => $images,
        ];
      }
      return [
        'type' => 'image',
        'data' => $images[0] ?? [],
      ];
    }

    // Extract links via DOM (more robust than regex)
    $linkNodes = $xpath->query('//a[@href]');
    if ($linkNodes->length > 0) {
      $links = [];
      foreach ($linkNodes as $link) {
        /** @var \DOMElement $link */
        $links[] = [
          'text' => trim(strip_tags($link->textContent ?? '')),
          'href' => $link->getAttribute('href') ?? '',
        ];
      }

      if (count($links) > 1) {
        return [
          'type' => 'links',
          'data' => $links,
        ];
      }
      return [
        'type' => 'link',
        'data' => $links[0] ?? [],
      ];
    }

    // Default to text
    return [
      'type' => 'text',
      'data' => [],
    ];
  }

  private function parseSectionContent(string $sectionMarkdown): array
  {
    // This will contain the core logic from the original extractDefaults loop
    $fields = $this->parseFieldMarkers($sectionMarkdown);

    // Remove ALL markers: fields, closers, subsections
    // Order matters: match extended fields (with ...) before regular fields
    $sectionMarkdownClean = preg_replace(
      '/<!--\s*(?:[a-zA-Z0-9_-]+\.{3}|\/sub:[a-zA-Z0-9_-]+|\/[a-zA-Z0-9_-]+|sub:[a-zA-Z0-9_-]+|\/sub|[a-zA-Z0-9_-]+|\/)\s*-->/m',
      '',
      $sectionMarkdown,
    );
    $html = $this->parsedown->text($sectionMarkdownClean);

    // ... extract title, contentHtml, plainText ...
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8"?><root>' . $html . '</root>');
    libxml_use_internal_errors(false);

    $xpath = new \DOMXPath($dom);
    $firstHeading = $xpath
      ->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6')
      ->item(0);
    $title = $firstHeading
      ? trim(strip_tags($firstHeading->textContent ?? ''))
      : '';

    $contentHtml = '';
    foreach (
      $dom->getElementsByTagName('root')->item(0)?->childNodes ?? []
      as $node
    ) {
      $contentHtml .= $this->serializeNode($node);
    }
    $plainText = $this->htmlToText($contentHtml);

    $blocks = $this->parseBlocks($contentHtml, $sectionMarkdown);

    return [
      'title' => $title,
      'html' => trim($contentHtml),
      'text' => $plainText,
      'blocks' => $blocks,
      'fields' => $fields,
    ];
  }

  /**
   * Extract default content elements from parsed Markdown sections
   *
   * @param array $sections Array of section data with 'name' and 'content' keys
   * @return ContentData Standardized content structure with defaults
   */
  private function extractDefaults(array $sections): ContentData
  {
    $sectionsData = [];
    $globalIndex = 0; // Track all sections regardless of name
    $unnamedIndex = 0;

    foreach ($sections as $section) {
      $sectionMarkdown = $section['content'];
      $sectionName = $section['name'];

      if (empty(trim($sectionMarkdown))) {
        continue;
      }

      $subsectionsData = [];
      $mainSectionMarkdown = $sectionMarkdown;

      // Match subsection openers and closers
      // Note: <!-- / --> is NOT matched here - it's only for fields
      preg_match_all(
        '/<!-- (?:sub:(\w+)|\/sub(?::(\w+))?) -->/m',
        $sectionMarkdown,
        $allMatches,
        PREG_OFFSET_CAPTURE,
      );

      if (!empty($allMatches[0])) {
        $subsectionRanges = [];
        $openStack = [];

        foreach ($allMatches[0] as $i => $match) {
          $fullMatch = $match[0];
          $position = $match[1];

          // Check if it's an opener (sub:name)
          if (!empty($allMatches[1][$i][0])) {
            $subName = $allMatches[1][$i][0];
            $openStack[] = [
              'name' => $subName,
              'start' => $position + strlen($fullMatch),
              'index' => $i,
            ];
          }
          // Check if it's a closer (/sub or /sub:name)
          // Note: <!-- / --> is NOT handled here, only explicit subsection closers
          elseif (preg_match('/<!-- \/sub(?::(\w+))? -->/', $fullMatch)) {
            if (!empty($openStack)) {
              // Check if it's <!-- /sub:name --> (named subsection closer)
              if (!empty($allMatches[2][$i][0])) {
                $closerName = $allMatches[2][$i][0];
                // Find and close the specific subsection with this name
                for (
                  $stackIdx = count($openStack) - 1;
                  $stackIdx >= 0;
                  $stackIdx--
                ) {
                  if ($openStack[$stackIdx]['name'] === $closerName) {
                    $opener = array_splice($openStack, $stackIdx, 1)[0];
                    $subsectionRanges[] = [
                      'name' => $opener['name'],
                      'start' => $opener['start'],
                      'end' => $position,
                    ];
                    break;
                  }
                }
              } else {
                // Explicit closer <!-- /sub --> - close most recent subsection
                $opener = array_pop($openStack);
                $subsectionRanges[] = [
                  'name' => $opener['name'],
                  'start' => $opener['start'],
                  'end' => $position,
                ];
              }
            }
            // If no opener to close, silently ignore the closer
          }
        }

        // Handle unclosed subsections (extend to next sub or end of section)
        while (!empty($openStack)) {
          $opener = array_pop($openStack);
          $nextOpenerPos = null;

          // Find the next subsection opener after this one
          foreach ($allMatches[0] as $j => $match) {
            if (
              $j > $opener['index'] &&
              !empty($allMatches[1][$j][0]) &&
              $match[1] > $opener['start']
            ) {
              $nextOpenerPos = $match[1];
              break;
            }
          }

          $subsectionRanges[] = [
            'name' => $opener['name'],
            'start' => $opener['start'],
            'end' => $nextOpenerPos ?? strlen($sectionMarkdown),
          ];
        }

        // Now extract content for each subsection range
        foreach ($subsectionRanges as $range) {
          $subSectionContent = trim(
            substr(
              $sectionMarkdown,
              $range['start'],
              $range['end'] - $range['start'],
            ),
          );

          if (empty($subSectionContent)) {
            continue;
          }

          $parsedSubContent = $this->parseSectionContent($subSectionContent);

          $subsectionsData[$range['name']] = new Section(
            title: $parsedSubContent['title'],
            html: $parsedSubContent['html'],
            text: $parsedSubContent['text'],
            blocks: $parsedSubContent['blocks'],
            fields: $parsedSubContent['fields'],
            subsections: [],
          );
        }
      }

      $parsedMainContent = $this->parseSectionContent($mainSectionMarkdown);

      $sectionObj = new Section(
        title: $parsedMainContent['title'],
        html: $parsedMainContent['html'],
        text: $parsedMainContent['text'],
        blocks: $parsedMainContent['blocks'],
        fields: $parsedMainContent['fields'],
        subsections: $subsectionsData,
      );

      // Store by name if provided
      if ($sectionName) {
        $sectionsData[$sectionName] = $sectionObj;
      } else {
        // Unnamed sections get both numeric index and global index
        $sectionsData[$unnamedIndex] = $sectionObj;
        $unnamedIndex++;
      }

      // Also store every section by global index for debugging
      $sectionsData[$globalIndex] = $sectionObj;
      $globalIndex++;
    }

    return new ContentData([
      'title' => '',
      'description' => '',
      'text' => '',
      'html' => '',
      'sections' => $sectionsData,
    ]);
  }

  /**
   * Parse section HTML into hierarchical blocks using DOM parsing
   *
   * Walks the DOM tree to identify heading levels and builds block structures,
   * extracting images, links, lists, and paragraphs from each block's content.
   *
   * @param string $html HTML content to parse
   * @param string|null $markdown Optional: original markdown for field extraction
   * @return array Array of Block objects organized hierarchically
   */
  private function parseBlocks(string $html, ?string $markdown = null): array
  {
    // Build a map of heading text to markdown block for field extraction
    $markdownBlocksByHeading = [];
    if ($markdown) {
      // Split markdown by headings to get block-level markdown
      $parts = preg_split(
        '/^(#{1,6} .*)$/m',
        $markdown,
        -1,
        PREG_SPLIT_DELIM_CAPTURE,
      );

      // Parts are: [content_before_first_heading, heading1, content1, heading2, content2, ...]
      for ($i = 1; $i < count($parts); $i += 2) {
        $headingLine = $parts[$i];
        $blockMarkdown = $parts[$i + 1] ?? '';

        // Extract heading text (remove # symbols)
        $headingText = trim(preg_replace('/^#+\s*/', '', $headingLine));
        $markdownBlocksByHeading[$headingText] = $blockMarkdown;
      }
    }

    // Wrap content in a root element for consistent DOM parsing
    $wrappedHtml = '<root>' . $html . '</root>';

    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8"?>' . $wrappedHtml);
    libxml_use_internal_errors(false);

    $xpath = new \DOMXPath($dom);

    // Find all headings (h1-h6) in document order
    $headingNodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

    if ($headingNodes->length === 0) {
      // No headings found, create a single block for all content
      $blocks = [];
      $allContent = $xpath->query('/root/*');
      if ($allContent->length > 0) {
        $allContentArray = $this->nodeListToArray($allContent);

        // Serialize content to string to extract fields
        $contentHtmlString = '';
        foreach ($allContentArray as $node) {
          $contentHtmlString .= $this->serializeNode($node);
        }

        $blockData = $this->extractBlockContent($allContentArray, $xpath);
        $blocks[] = [
          'heading' => '',
          'level' => 0,
          'content' => $blockData['html'],
          'images' => $blockData['images'],
          'links' => $blockData['links'],
          'lists' => $blockData['lists'],
          'paragraphs' => $blockData['paragraphs'],
          'headings' => $blockData['headings'],
          'fields' => $this->parseFieldMarkers($contentHtmlString),
          'text' => $blockData['text'],
          'html' => $blockData['html'],
        ];
      }
      return $this->buildHierarchy($blocks);
    }

    $blocks = [];

    // Convert NodeList to array for easier manipulation
    $headingArray = $this->nodeListToArray($headingNodes);

    // Check if the content starts with h2+ (no h1 at the start)
    // If so, we'll need to create a synthetic root block to wrap them
    $firstHeadingLevel = (int) substr($headingArray[0]->nodeName, 1);
    $needsSyntheticRoot = $firstHeadingLevel > 1;

    // If we need a synthetic root, collect all content before first heading
    $syntheticRootContent = [];
    if ($needsSyntheticRoot) {
      $currentNode = $dom->firstChild->firstChild; // Get first element in /root
      while (
        $currentNode !== null &&
        !(
          $currentNode->nodeType === XML_ELEMENT_NODE &&
          preg_match('/^h[1-6]$/i', $currentNode->nodeName)
        )
      ) {
        if ($currentNode->nodeType === XML_ELEMENT_NODE) {
          $syntheticRootContent[] = $currentNode;
        }
        $currentNode = $currentNode->nextSibling;
      }
    }

    // For each heading, collect all nodes until the next heading at same or higher level
    for ($i = 0; $i < count($headingArray); $i++) {
      $currentHeading = $headingArray[$i];
      $currentLevel = (int) substr($currentHeading->nodeName, 1);

      // Collect nodes from after this heading until the next heading
      $contentNodes = [];
      $nextNode = $currentHeading->nextSibling;

      while ($nextNode !== null) {
        // Check if this node is a heading of any level (stop condition)
        if (
          $nextNode->nodeType === XML_ELEMENT_NODE &&
          preg_match('/^h[1-6]$/i', $nextNode->nodeName)
        ) {
          break;
        }

        if ($nextNode->nodeType === XML_ELEMENT_NODE) {
          $contentNodes[] = $nextNode;
        }

        $nextNode = $nextNode->nextSibling;
      }

      // Build the heading HTML using DOM serialization
      $headingHtml = $this->serializeNode($currentHeading);

      // Extract heading text for markdown lookup
      $headingText = trim(strip_tags($headingHtml));

      // Get the markdown block for this heading to extract fields
      $blockMarkdown = $markdownBlocksByHeading[$headingText] ?? null;

      // Serialize content nodes to HTML string to extract field markers BEFORE losing comments
      $contentHtmlString = '';
      foreach ($contentNodes as $node) {
        $contentHtmlString .= $this->serializeNode($node);
      }

      // Extract field markers from markdown if available, otherwise from HTML
      $blockFields = $blockMarkdown
        ? $this->parseFieldMarkers($blockMarkdown)
        : $this->parseFieldMarkers($contentHtmlString);

      // Extract content from collected nodes
      $blockData = $this->extractBlockContent($contentNodes, $xpath);

      $blocks[] = [
        'heading' => $headingHtml,
        'level' => $currentLevel,
        'content' => $blockData['html'],
        'images' => $blockData['images'],
        'links' => $blockData['links'],
        'lists' => $blockData['lists'],
        'paragraphs' => $blockData['paragraphs'],
        'fields' => $blockFields,
        'text' => $this->htmlToText($headingHtml . $blockData['html']),
        'html' => $headingHtml . $blockData['html'],
      ];
    }

    // If we created a synthetic root, prepend it to the blocks
    // so that all the h2+ blocks become its children
    if ($needsSyntheticRoot && !empty($blocks)) {
      $syntheticBlockData = [];
      $syntheticBlockFields = [];
      if (!empty($syntheticRootContent)) {
        // Serialize content to string to extract fields
        $syntheticHtmlString = '';
        foreach ($syntheticRootContent as $node) {
          $syntheticHtmlString .= $this->serializeNode($node);
        }
        $syntheticBlockFields = $this->parseFieldMarkers($syntheticHtmlString);

        $syntheticBlockData = $this->extractBlockContent(
          $syntheticRootContent,
          $xpath,
        );
      }

      // Adjust all existing blocks to have level+1 (they'll become children of synthetic root)
      foreach ($blocks as &$block) {
        $block['level'] = $block['level'] + 1;
      }

      // Create the synthetic root block at level 1
      array_unshift($blocks, [
        'heading' => '',
        'level' => 1,
        'content' => $syntheticBlockData['html'] ?? '',
        'images' =>
          $syntheticBlockData['images'] ?? new ContentElementCollection(),
        'links' =>
          $syntheticBlockData['links'] ?? new ContentElementCollection(),
        'lists' =>
          $syntheticBlockData['lists'] ?? new ContentElementCollection(),
        'paragraphs' =>
          $syntheticBlockData['paragraphs'] ?? new ContentElementCollection(),
        'fields' => $syntheticBlockFields,
        'text' => $syntheticBlockData['text'] ?? '',
        'html' => $syntheticBlockData['html'] ?? '',
      ]);
    }

    // Build hierarchical structure
    return $this->buildHierarchy($blocks);
  }

  /**
   * Convert DOMNodeList to array for easier iteration
   *
   * @param \DOMNodeList $nodeList
   * @return array Array of nodes
   */
  private function nodeListToArray(\DOMNodeList $nodeList): array
  {
    $array = [];
    foreach ($nodeList as $node) {
      $array[] = $node;
    }
    return $array;
  }

  /**
   * Extract content from DOM nodes within a block
   *
   * @param array $nodes Array of DOMElement nodes
   * @param \DOMXPath $xpath XPath evaluator for querying
   * @return array Array with 'html', 'text', 'images', 'links', 'lists', 'paragraphs', 'headings' keys
   */
  private function extractBlockContent(array $nodes, \DOMXPath $xpath): array
  {
    $html = '';
    $images = [];
    $links = [];
    $lists = [];
    $paragraphs = [];
    $headings = [];

    // Use associative arrays to track uniqueness
    $seenImages = [];
    $seenLinks = [];
    $seenLists = [];
    $seenParagraphs = [];

    foreach ($nodes as $node) {
      // Skip heading nodes - they belong to child blocks, not this block's direct content
      if (
        $node->nodeType === XML_ELEMENT_NODE &&
        preg_match('/^h[1-6]$/i', $node->nodeName)
      ) {
        // Still include the heading in HTML for structure, but don't extract its content
        $html .= $this->serializeNode($node);
        continue;
      }

      $html .= $this->serializeNode($node); // Extract images from this node using XPath (deduplicated by src)
      $nodeImages = $xpath->query('.//img', $node);
      foreach ($nodeImages as $imgNode) {
        /** @var \DOMElement $imgNode */
        $key = spl_object_id($imgNode);
        if (!isset($seenImages[$key])) {
          $seenImages[$key] = true;
          $src = $imgNode->getAttribute('src') ?? '';
          $alt = $imgNode->getAttribute('alt') ?? '';
          $images[] = new ContentElement(
            text: "[$alt]",
            html: "<img src=\"$src\" alt=\"$alt\">",
            data: ['src' => $src, 'alt' => $alt],
          );
        }
      }

      // Extract links from this node using XPath (deduplicated by href+text)
      $nodeLinks = $xpath->query('.//a[@href]', $node);
      foreach ($nodeLinks as $linkNode) {
        /** @var \DOMElement $linkNode */
        $href = $linkNode->getAttribute('href') ?? '';
        $linkHtml = $this->serializeNode($linkNode);
        $linkText = trim(strip_tags($linkHtml));

        // Deduplicate by href + text combination
        $linkKey = $href . '|' . $linkText;
        if (!isset($seenLinks[$linkKey])) {
          $seenLinks[$linkKey] = true;
          $links[] = new ContentElement(
            text: $linkText,
            html: $linkHtml,
            data: ['href' => $href],
          );
        }
      }

      // Extract lists from this node using XPath
      // Check if node itself is a list, or search within it
      $nodeLists = [];
      if (
        $node->nodeType === XML_ELEMENT_NODE &&
        ($node->nodeName === 'ul' || $node->nodeName === 'ol')
      ) {
        $nodeLists = [$node];
      } else {
        $nodeListsQuery = $xpath->query('.//ul | .//ol', $node);
        $nodeLists = iterator_to_array($nodeListsQuery);
      }

      foreach ($nodeLists as $listNode) {
        /** @var \DOMElement $listNode */
        $listHtml = $this->serializeNode($listNode);
        $listType = $listNode->nodeName;

        // Extract list items
        $items = [];
        $listItems = $xpath->query('.//li', $listNode);
        foreach ($listItems as $liNode) {
          $items[] = trim(strip_tags($this->serializeNode($liNode)));
        }

        // Deduplicate by HTML content (lists are unique by their full content)
        $listKey = md5($listHtml);
        if (!isset($seenLists[$listKey])) {
          $seenLists[$listKey] = true;
          $lists[] = new ContentElement(
            text: strip_tags($listHtml),
            html: $listHtml,
            data: [
              'type' => $listType,
              'items' => $items,
            ],
          );
        }
      }

      // Extract paragraphs from this node using XPath
      // Check if node itself is a paragraph, or search within it
      $nodeParagraphs = [];
      if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === 'p') {
        $nodeParagraphs = [$node];
      } else {
        $nodeParagraphsQuery = $xpath->query('.//p', $node);
        $nodeParagraphs = iterator_to_array($nodeParagraphsQuery);
      }

      foreach ($nodeParagraphs as $pNode) {
        $pHtml = $this->serializeNode($pNode);

        // Deduplicate by HTML content
        $pKey = md5($pHtml);
        if (!isset($seenParagraphs[$pKey])) {
          $seenParagraphs[$pKey] = true;
          $paragraphs[] = new ContentElement(
            text: trim(strip_tags($pHtml)),
            html: $pHtml,
            data: [],
          );
        }
      }

      // NOTE: We do NOT extract headings as content elements here.
      // Headings are structural markers that create blocks/children,
      // not content elements like paragraphs or images.
      // If you need to access a block's heading, use $block->heading
      // If you need all headings in a hierarchy, use $block->allHeadings or $section->headings
    }

    return [
      'html' => $html,
      'text' => $this->htmlToText($html),
      'images' => new ContentElementCollection($images),
      'links' => new ContentElementCollection($links),
      'lists' => new ContentElementCollection($lists),
      'paragraphs' => new ContentElementCollection($paragraphs),
    ];
  }

  /**
   * Serialize a DOM node to HTML string
   *
   * Note: DOMDocument::saveHTML() normalizes HTML entities. For example:
   * - Smart quotes become &quot; and &apos;
   * - Ampersands become &amp;
   * - Special characters get numeric entity encoding
   *
   * This normalization is generally beneficial for rendering consistency
   * and security, but should be considered when comparing serialized output
   * to raw HTML strings. It does NOT affect content meaning or display, only
   * representation.
   *
   * @param \DOMNode $node The node to serialize
   * @return string HTML representation of the node with normalized entities
   */
  private function serializeNode(\DOMNode $node): string
  {
    $dom = new \DOMDocument();
    $dom->appendChild($dom->importNode($node, true));
    $html = $dom->saveHTML();

    // For text nodes, return the text content directly
    if ($node->nodeType === XML_TEXT_NODE) {
      return $node->textContent;
    }

    // For element nodes (like <p>, <ul>, etc.), preserve the full tag including attributes
    // Remove only the XML declaration wrapper, keep the element and its content
    $html = preg_replace('/^<\?xml[^?]*?\?>/', '', $html);
    $html = preg_replace('/<root>|<\/root>/', '', $html);

    return trim($html);
  }

  /**
   * Build hierarchical block structure based on heading levels
   */
  private function buildHierarchy(array $blocks): array
  {
    if (empty($blocks)) {
      return [];
    }

    $root = [];
    $stack = []; // Stack to track parent blocks

    foreach ($blocks as $blockData) {
      // Create Block object without children first
      $block = new Block(
        heading: $blockData['heading'],
        level: $blockData['level'],
        content: $blockData['content'],
        html: $blockData['html'],
        text: $blockData['text'],
        paragraphs: $blockData['paragraphs'],
        images: $blockData['images'],
        links: $blockData['links'],
        lists: $blockData['lists'],
        children: [],
        fields: $blockData['fields'],
      );

      // Find the appropriate parent
      while (!empty($stack) && end($stack)->level >= $block->level) {
        array_pop($stack);
      }

      if (empty($stack)) {
        // Top-level block
        $root[] = $block;
      } else {
        // Child block - add to parent
        $parent = end($stack);
        $parent->children[] = $block;
      }

      // Push current block onto stack
      $stack[] = $block;
    }

    // Update html and text for all blocks to include children
    $this->updateBlockHtml($root);

    return $root;
  }

  /**
   * Recursively update block html and text to include all children
   */
  private function updateBlockHtml(array $blocks): void
  {
    foreach ($blocks as $block) {
      if (!empty($block->children)) {
        // First, update children recursively
        $this->updateBlockHtml($block->children);

        // Collect all children HTML
        $childrenHtml = '';
        $childrenText = '';
        foreach ($block->children as $child) {
          $childrenHtml .= $child->html;
          $childrenText .= "\n" . $child->text;
        }

        // Update this block's html and text to include children's aggregated content
        // Skip updating html/text for synthetic root blocks (level 1, empty heading)
        if (!($block->level === 1 && empty($block->heading->text))) {
          $block->html .= $childrenHtml;
          $block->text .= $childrenText;
        }
      }
    }
  }

  /**
   * Convert HTML to readable text with proper line breaks between block elements
   */
  private function htmlToText(string $html): string
  {
    // Add line breaks between block elements for better text readability
    $htmlForText = preg_replace(
      '~</(p|h[1-6]|ul|ol|blockquote)>~i',
      "$0\n\n",
      $html,
    );
    $htmlForText = preg_replace('~</li>~i', "$0\n", $htmlForText ?? '');
    $plainText = trim(strip_tags($htmlForText ?? ''));
    // Normalize excessive line breaks to max 2 consecutive
    $plainText = preg_replace('~\n{3,}~', "\n\n", $plainText);
    return $plainText;
  }
}

/**
 * ContentData: Data container for parsed Markdown content
 *
 * Provides both array and object access to extracted content elements
 */
class ContentData
{
  public string $title;
  public string $description;
  public string $text;
  public string $html;
  public array $sections;

  public function __construct(array $data = [])
  {
    $this->title = $data['title'] ?? '';
    $this->description = $data['description'] ?? '';
    $this->text = $data['text'] ?? '';
    $this->html = $data['html'] ?? '';
    $this->sections = $data['sections'] ?? [];
  }

  public function __get($name)
  {
    if (isset($this->sections[$name])) {
      return $this->sections[$name];
    }

    return match ($name) {
      'headings' => $this->getHeadings(),
      'blocks' => $this->getBlocks(),
      'images' => $this->getImages(),
      'links' => $this->getLinks(),
      'lists' => $this->getLists(),
      'paragraphs' => $this->getParagraphs(),
      default => null,
    };
  }

  public function section(string $name): ?Section
  {
    return $this->sections[$name] ?? null;
  }

  /**
   * Get a deduplicated list of sections.
   * Sections can be present multiple times in the sections array (by numeric index and by name).
   * This method returns an array of unique section objects.
   */
  private function getUniqueSections(): array
  {
    $unique = [];
    foreach ($this->sections as $section) {
      if (!in_array($section, $unique, true)) {
        $unique[] = $section;
      }
    }
    return $unique;
  }

  // Helper methods for flattened content
  private function getHeadings(): array
  {
    $headings = [];
    $seen = [];

    foreach ($this->getUniqueSections() as $section) {
      foreach ($section->blocks as $block) {
        $this->collectHeadingsFromBlock($block, $headings, $seen);
      }
    }
    return $headings;
  }

  private function getBlocks(): array
  {
    $blocks = [];
    foreach ($this->getUniqueSections() as $section) {
      $blocks = array_merge($blocks, $section->blocks);
    }
    return $blocks;
  }

  private function getImages(): ContentElementCollection
  {
    $images = [];
    foreach ($this->getUniqueSections() as $section) {
      $images = array_merge($images, $section->images->getArrayCopy());
    }
    return new ContentElementCollection($images);
  }

  private function getLinks(): ContentElementCollection
  {
    $links = [];
    foreach ($this->getUniqueSections() as $section) {
      $links = array_merge($links, $section->links->getArrayCopy());
    }
    return new ContentElementCollection($links);
  }

  private function getLists(): ContentElementCollection
  {
    $lists = [];
    foreach ($this->getUniqueSections() as $section) {
      $lists = array_merge($lists, $section->lists->getArrayCopy());
    }
    return new ContentElementCollection($lists);
  }

  private function getParagraphs(): ContentElementCollection
  {
    $paragraphs = [];
    foreach ($this->getUniqueSections() as $section) {
      $paragraphs = array_merge(
        $paragraphs,
        $section->paragraphs->getArrayCopy(),
      );
    }
    return new ContentElementCollection($paragraphs);
  }

  private function collectHeadingsFromBlock(
    Block $block,
    array &$headings,
    array &$seen,
  ): void {
    // Add the block's own heading (avoid duplicates)
    if ($block->heading && $block->heading->text !== '') {
      $key = $block->heading->text . '|' . $block->level;
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        $headings[] = new ContentElement(
          text: $block->heading->text,
          html: $block->heading->html,
          data: ['level' => $block->level],
        );
      }
    }

    // Recursively collect from children
    foreach ($block->children as $child) {
      $this->collectHeadingsFromBlock($child, $headings, $seen);
    }
  }

  private function collectHeadingsFromBlockChildrenOnly(
    Block $block,
    array &$headings,
    array &$seen,
  ): void {
    // Only collect from children, skip the block's own heading
    foreach ($block->children as $child) {
      $this->collectHeadingsFromBlock($child, $headings, $seen);
    }
  }
}

/**
 * ContentElement: Simple container for content with text/html access
 */
class ContentElement
{
  public function __construct(
    public string $text,
    public string $html,
    public array $data = [],
  ) {}

  public function __toString(): string
  {
    return $this->text; // Default to text when used as string
  }

  public function __get($name)
  {
    return $this->data[$name] ?? null;
  }
}

/**
 * HeadingElement: Container for heading with both HTML and text extraction
 *
 * Allows access to heading in multiple forms:
 * - (string) cast or ->html: Returns full HTML like "<h3>foo</h3>"
 * - ->text: Returns extracted text like "foo"
 */
class HeadingElement
{
  public readonly string $text;
  public readonly string $html;

  public function __construct(string $heading)
  {
    $this->html = $heading;
    // Extract text by stripping HTML tags and decoding entities
    $this->text = html_entity_decode(strip_tags($heading));
  }

  public function __toString(): string
  {
    return $this->html; // Default to HTML for backward compatibility
  }
}

/**
 * Block: Container for content between headings
 */
class Block
{
  public HeadingElement|string $heading;

  public function __construct(
    HeadingElement|string $heading,
    public int $level,
    public string $content,
    public string $html,
    public string $text,
    public ContentElementCollection $paragraphs,
    public ContentElementCollection $images,
    public ContentElementCollection $links,
    public ContentElementCollection $lists,
    public array $children = [],
    public array $fields = [],
  ) {
    // Ensure heading is a HeadingElement
    if (is_string($heading)) {
      $this->heading = new HeadingElement($heading);
    } else {
      $this->heading = $heading;
    }
  }

  public function __get($name)
  {
    // 1. Check for a field with the given name
    if (isset($this->fields[$name])) {
      return $this->fields[$name];
    }

    // 2. Fall back to generic content collections
    return match ($name) {
      'headings' => $this->getAllHeadings(),
      'allHeadings' => $this->getAllHeadings(),
      'allImages' => $this->getAllImages(),
      'allLinks' => $this->getAllLinks(),
      'allLists' => $this->getAllLists(),
      'allParagraphs' => $this->getAllParagraphs(),
      default => null,
    };
  }

  /**
   * Get a field by name
   *
   * @param string $name Field name
   * @return FieldData|null Field data or null if not found
   */
  public function field(string $name): ?FieldData
  {
    return $this->fields[$name] ?? null;
  }

  private function getAllHeadings(): array
  {
    $headings = [];
    $seen = [];

    // Add our own heading if we have one
    if ($this->heading && $this->heading->text !== '') {
      $key = $this->heading->text . '|' . $this->level;
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        $headings[] = new ContentElement(
          text: $this->heading->text,
          html: $this->heading->html,
          data: ['level' => $this->level],
        );
      }
    }

    // Recursively collect from children
    foreach ($this->children as $child) {
      $this->collectHeadingsFromChildren($child, $headings, $seen);
    }

    return $headings;
  }

  private function collectHeadingsFromChildren(
    Block $block,
    array &$headings,
    array &$seen,
  ): void {
    // Add the block's own heading (avoid duplicates)
    if ($block->heading && $block->heading->text !== '') {
      $key = $block->heading->text . '|' . $block->level;
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        $headings[] = new ContentElement(
          text: $block->heading->text,
          html: $block->heading->html,
          data: ['level' => $block->level],
        );
      }
    }

    // Recursively collect from children
    foreach ($block->children as $child) {
      $this->collectHeadingsFromChildren($child, $headings, $seen);
    }
  }

  public function getAllImages(): ContentElementCollection
  {
    $images = [];
    $seen = [];

    // Add direct images from this block
    foreach ($this->images as $img) {
      $key = spl_object_id($img); // Use object identity to avoid deduplicating user's intentional duplicates
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        $images[] = $img;
      }
    }

    // Recursively collect from children
    foreach ($this->children as $child) {
      foreach ($child->getAllImages() as $img) {
        $key = spl_object_id($img);
        if (!isset($seen[$key])) {
          $seen[$key] = true;
          $images[] = $img;
        }
      }
    }

    return new ContentElementCollection($images);
  }

  public function getAllLinks(): ContentElementCollection
  {
    $links = [];
    $seen = [];

    // Add direct links from this block
    foreach ($this->links as $link) {
      $key = spl_object_id($link);
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        $links[] = $link;
      }
    }

    // Recursively collect from children
    foreach ($this->children as $child) {
      foreach ($child->getAllLinks() as $link) {
        $key = spl_object_id($link);
        if (!isset($seen[$key])) {
          $seen[$key] = true;
          $links[] = $link;
        }
      }
    }

    return new ContentElementCollection($links);
  }

  public function getAllLists(): ContentElementCollection
  {
    $lists = [];
    $seen = [];

    // Add direct lists from this block
    foreach ($this->lists as $list) {
      $key = spl_object_id($list);
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        $lists[] = $list;
      }
    }

    // Recursively collect from children
    foreach ($this->children as $child) {
      foreach ($child->getAllLists() as $list) {
        $key = spl_object_id($list);
        if (!isset($seen[$key])) {
          $seen[$key] = true;
          $lists[] = $list;
        }
      }
    }

    return new ContentElementCollection($lists);
  }

  public function getAllParagraphs(): ContentElementCollection
  {
    $paragraphs = [];
    $seen = [];

    // Add direct paragraphs from this block
    foreach ($this->paragraphs as $para) {
      $key = spl_object_id($para);
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        $paragraphs[] = $para;
      }
    }

    // Recursively collect from children
    foreach ($this->children as $child) {
      foreach ($child->getAllParagraphs() as $para) {
        $key = spl_object_id($para);
        if (!isset($seen[$key])) {
          $seen[$key] = true;
          $paragraphs[] = $para;
        }
      }
    }

    return new ContentElementCollection($paragraphs);
  }
}

/**
 * Section: Container for markdown sections
 */
class Section
{
  public function __construct(
    public string $title,
    public string $html,
    public string $text,
    protected array $blocks,
    public array $fields = [],
    public array $subsections = [],
  ) {}

  public function __get($name)
  {
    // 1. Check for a subsection with the given name
    if (isset($this->subsections[$name])) {
      return $this->subsections[$name];
    }

    // 2. Check for a field with the given name
    if (isset($this->fields[$name])) {
      return $this->fields[$name];
    }

    // 3. Fall back to generic content properties
    return match ($name) {
      'headings' => $this->getHeadings(),
      'images' => $this->getImages(),
      'links' => $this->getLinks(),
      'lists' => $this->getLists(),
      'paragraphs' => $this->getParagraphs(),
      'blocks'
        => $this->getRealBlocks(), // Use the new method to get real blocks
      default => null,
    };
  }

  public function subsection(string $name): ?self
  {
    return $this->subsections[$name] ?? null;
  }

  /**

     * Get a field by name

     *

     * @param string $name Field name

     * @return FieldData|null Field data or null if not found

     */

  public function field(string $name): ?FieldData
  {
    return $this->fields[$name] ?? null;
  }

  private function getHeadings(): array
  {
    $headings = [];

    $seen = [];

    foreach ($this->blocks as $block) {
      $this->collectHeadingsFromBlock($block, $headings, $seen);
    }

    return $headings;
  }

  private function collectHeadingsFromBlock(
    Block $block,
    array &$headings,
    array &$seen,
  ): void {
    // Add the block's own heading (avoid duplicates)

    if ($block->heading && $block->heading->text !== '') {
      $key = $block->heading->text . '|' . $block->level;

      if (!isset($seen[$key])) {
        $seen[$key] = true;

        $headings[] = new ContentElement(
          text: $block->heading->text,
          html: $block->heading->html,
          data: ['level' => $block->level],
        );
      }
    }

    // Recursively collect from children

    foreach ($block->children as $child) {
      $this->collectHeadingsFromBlock($child, $headings, $seen);
    }
  }

  private function getImages(): ContentElementCollection
  {
    $images = [];

    foreach ($this->blocks as $block) {
      $images = array_merge($images, $block->getAllImages()->getArrayCopy());
    }

    return new ContentElementCollection($images);
  }

  private function getLinks(): ContentElementCollection
  {
    $links = [];

    foreach ($this->blocks as $block) {
      $links = array_merge($links, $block->getAllLinks()->getArrayCopy());
    }

    return new ContentElementCollection($links);
  }

  private function getLists(): ContentElementCollection
  {
    $lists = [];

    foreach ($this->blocks as $block) {
      $lists = array_merge($lists, $block->getAllLists()->getArrayCopy());
    }

    return new ContentElementCollection($lists);
  }

  private function getParagraphs(): ContentElementCollection
  {
    $paragraphs = [];

    foreach ($this->blocks as $block) {
      $paragraphs = array_merge(
        $paragraphs,
        $block->getAllParagraphs()->getArrayCopy(),
      );
    }

    return new ContentElementCollection($paragraphs);
  }

  public function getRealBlocks(): array
  {
    // Check if the first block is a synthetic root (level 1, empty heading)

    if (
      !empty($this->blocks) &&
      $this->blocks[0]->level === 1 &&
      empty($this->blocks[0]->heading->text)
    ) {
      // If it's a synthetic root, return its children

      return $this->blocks[0]->children;
    }

    // Otherwise, return the blocks as they are

    return $this->blocks;
  }
}

/**

   * FieldData: Container for field-tagged content within sections

   */
class FieldData
{
  private ?array $contentElements = null;

  public function __construct(
    public string $name,
    public string $markdown,
    public string $html,
    public string $text,
    public string $type,
    public array $data = [],
  ) {}

  public function __toString(): string
  {
    return $this->text;
  }

  public function __get($key)
  {
    if ($key === 'items') {
      if ($this->type === 'list') {
        return $this->data ?? [];
      }
      if (in_array($this->type, ['images', 'links'])) {
        if ($this->contentElements === null) {
          $this->contentElements = $this->toContentElements();
        }
        return $this->contentElements;
      }
    }

    // For single item fields, allow direct property access
    if (in_array($this->type, ['image', 'link'])) {
      return $this->data[$key] ?? null;
    }

    return $this->data[$key] ?? null;
  }

  /**
   * Convert multi-item data to ContentElement objects
   */
  private function toContentElements(): array
  {
    if ($this->type === 'images') {
      return array_map(
        fn($img) => new ContentElement(
          text: $img['alt'] ?? '',
          html: '<img src="' .
            htmlspecialchars($img['src']) .
            '" alt="' .
            htmlspecialchars($img['alt'] ?? '') .
            '">',
          data: $img,
        ),
        $this->data,
      );
    }

    if ($this->type === 'links') {
      return array_map(
        fn($link) => new ContentElement(
          text: $link['text'] ?? '',
          html: '<a href="' .
            htmlspecialchars($link['href']) .
            '">' .
            htmlspecialchars($link['text'] ?? '') .
            '</a>',
          data: $link,
        ),
        $this->data,
      );
    }

    return [];
  }
}

/**
 * ContentElementCollection: A collection of ContentElement objects
 *
 * Allows accessing the combined HTML or text of all elements in the collection.
 */
class ContentElementCollection extends \ArrayObject
{
  public function __get($name)
  {
    if ($name === 'html') {
      $html = '';
      foreach ($this as $element) {
        $html .= $element->html;
      }
      return $html;
    }
    if ($name === 'text') {
      $text = '';
      foreach ($this as $element) {
        $text .= $element->text . "\n\n";
      }
      return trim($text);
    }
    return null;
  }

  public function __toString(): string
  {
    return $this->text;
  }
}
