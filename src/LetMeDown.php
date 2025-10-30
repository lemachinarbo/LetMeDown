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
   * Extracts content tagged with <!-- fieldname --> markers
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

    // Split the markdown by field markers, keeping the delimiters
    $parts = preg_split(
      '/<!-- ([a-zA-Z0-9_-]+) -->/m',
      $markdown,
      -1,
      PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
    );

    $currentFieldName = null;
    $currentFieldContent = '';

    for ($i = 0; $i < count($parts); $i++) {
      $part = $parts[$i];

      // Check if it's a field name (e.g., 'text', 'image', 'cta')
      // A field name part will be a simple string like 'text', 'image', etc.
      // We can identify it by checking if it's not empty and doesn't contain markdown/html tags.
      // This regex checks if the part consists only of word characters and hyphens.
      if (preg_match('/^[a-zA-Z0-9_-]+$/', $part) && ($i === 0 || !preg_match('/<!-- ([a-zA-Z0-9_-]+) -->/m', $parts[$i-1]))) {
        // This is a field name
        if ($currentFieldName !== null && !isset($seenFieldNames[$currentFieldName])) {
          // Save the previous field's content
          $fieldMarkdown = trim($currentFieldContent);
          if (!empty($fieldMarkdown)) {
            $fieldHtml = $this->parsedown->text($fieldMarkdown);
            $fieldText = trim(strip_tags($fieldHtml));
            $fieldData = $this->extractFieldData($fieldMarkdown, $fieldHtml, $fieldText);

            $fields[$currentFieldName] = new FieldData(
              name: $currentFieldName,
              markdown: $fieldMarkdown,
              html: trim($fieldHtml),
              text: $fieldText,
              type: $fieldData['type'],
              data: $fieldData['data'],
            );
            $seenFieldNames[$currentFieldName] = true;
          }
        }
        $currentFieldName = $part;
        $currentFieldContent = ''; // Reset content for the new field
      } else {
        // This is content
        $currentFieldContent .= $part;
      }
    }

    // Save the last field's content if any
    if ($currentFieldName !== null && !isset($seenFieldNames[$currentFieldName])) {
      $fieldMarkdown = trim($currentFieldContent);
      if (!empty($fieldMarkdown)) {
        $fieldHtml = $this->parsedown->text($fieldMarkdown);
        $fieldText = trim(strip_tags($fieldHtml));
        $fieldData = $this->extractFieldData($fieldMarkdown, $fieldHtml, $fieldText);

        $fields[$currentFieldName] = new FieldData(
          name: $currentFieldName,
          markdown: $fieldMarkdown,
          html: trim($fieldHtml),
          text: $fieldText,
          type: $fieldData['type'],
          data: $fieldData['data'],
        );
      }
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
          $items[] = trim(strip_tags($liNode->textContent ?? ''));
        }
      }
      return [
        'type' => 'list',
        'data' => ['items' => $items],
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

      return [
        'type' => count($images) > 1 ? 'images' : 'image',
        'data' => count($images) > 1 ? $images : $images[0] ?? [],
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

      return [
        'type' => count($links) > 1 ? 'links' : 'link',
        'data' => count($links) > 1 ? $links : $links[0] ?? [],
      ];
    }

    // Default to text
    return [
      'type' => 'text',
      'data' => [],
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

      // Parse field markers within this section (only first occurrence of each field name)
      $fields = $this->parseFieldMarkers($sectionMarkdown);

      // Remove field markers before parsing with Parsedown
      // This ensures markdown is parsed correctly without HTML comment interference
      $sectionMarkdownClean = preg_replace(
        '/<!-- [a-zA-Z0-9_-]+ -->/m',
        '',
        $sectionMarkdown,
      );

      // Parse this section's markdown (without field markers)
      $html = $this->parsedown->text($sectionMarkdownClean);

      // Extract title from first heading (h1-h6) using DOM for consistency
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

      // Include all content including the title heading
      $contentHtml = '';
      foreach (
        $dom->getElementsByTagName('root')->item(0)?->childNodes ?? []
        as $node
      ) {
        $contentHtml .= $this->serializeNode($node);
      }

      // Also keep the markdown for block structure (remove field markers to avoid breaking parsing)
      $contentMarkdownClean = preg_replace(
        '/<!-- [a-zA-Z0-9_-]+ -->/m',
        '',
        $sectionMarkdown,
      );

      // Keep everything in html field, add plain text option
      $plainText = $this->htmlToText($contentHtml);

      // Parse section into hierarchical blocks (pass original markdown)
      $blocks = $this->parseBlocks($contentHtml, $sectionMarkdown);

      $sectionObj = new Section(
        title: $title,
        html: trim($contentHtml),
        text: $plainText,
        blocks: $blocks,
        fields: $fields,
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
        'images' => $syntheticBlockData['images'] ?? [],
        'links' => $syntheticBlockData['links'] ?? [],
        'lists' => $syntheticBlockData['lists'] ?? [],
        'paragraphs' => $syntheticBlockData['paragraphs'] ?? [],
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
      'images' => $images,
      'links' => $links,
      'lists' => $lists,
      'paragraphs' => $paragraphs,
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
      '/<\/(p|h[1-6]|ul|ol|blockquote)>/i',
      "$0\n\n",
      $html,
    );
    $htmlForText = preg_replace('/<\/li>/i', "$0\n", $htmlForText);
    $plainText = trim(strip_tags($htmlForText));
    // Normalize excessive line breaks to max 2 consecutive
    $plainText = preg_replace('/\n{3,}/', "\n\n", $plainText);
    return $plainText;
  }
}

/**
 * ContentData: Data container for parsed Markdown content
 *
 * Provides both array and object access to extracted content elements
 */
class ContentData extends \ArrayObject
{
    public function __construct(array $data = [])
    {
        parent::__construct($data, \ArrayObject::ARRAY_AS_PROPS);
    }

    public function __get($name)
    {
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

  private function getImages(): array
  {
    $images = [];
    foreach ($this->getUniqueSections() as $section) {
      $images = array_merge($images, $section->images);
    }
    return $images;
  }

  private function getLinks(): array
  {
    $links = [];
    foreach ($this->getUniqueSections() as $section) {
      $links = array_merge($links, $section->links);
    }
    return $links;
  }

  private function getLists(): array
  {
    $lists = [];
    foreach ($this->getUniqueSections() as $section) {
      $lists = array_merge($lists, $section->lists);
    }
    return $lists;
  }

  private function getParagraphs(): array
  {
    $paragraphs = [];
    foreach ($this->getUniqueSections() as $section) {
      $paragraphs = array_merge($paragraphs, $section->paragraphs);
    }
    return $paragraphs;
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
    public array $paragraphs,
    public array $images,
    public array $links,
    public array $lists,
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

  public function getAllImages(): array
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

    return $images;
  }

  public function getAllLinks(): array
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

    return $links;
  }

  public function getAllLists(): array
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

    return $lists;
  }

  public function getAllParagraphs(): array
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

    return $paragraphs;
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
  ) {}

  public function __get($name)
  {
    return match ($name) {
      'headings' => $this->getHeadings(),
      'images' => $this->getImages(),
      'links' => $this->getLinks(),
      'lists' => $this->getLists(),
      'paragraphs' => $this->getParagraphs(),
      'blocks' => $this->getRealBlocks(), // Use the new method to get real blocks
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

  

    private function getImages(): array

    {

      $images = [];

      foreach ($this->blocks as $block) {

        $images = array_merge($images, $block->getAllImages());

      }

      return $images;

    }

  

    private function getLinks(): array

    {

      $links = [];

      foreach ($this->blocks as $block) {

        $links = array_merge($links, $block->getAllLinks());

      }

      return $links;

    }

  

    private function getLists(): array

    {

      $lists = [];

      foreach ($this->blocks as $block) {

        $lists = array_merge($lists, $block->getAllLists());

      }

      return $lists;

    }

  

    private function getParagraphs(): array

    {

      $paragraphs = [];

      foreach ($this->blocks as $block) {

        $paragraphs = array_merge($paragraphs, $block->getAllParagraphs());

      }

      return $paragraphs;

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
    // For list fields, return items directly as array of strings
    if ($key === 'items' && $this->type === 'list') {
      return $this->data['items'] ?? [];
    }

    // For multi-item fields, provide easy access as ContentElements
    if ($key === 'items' && in_array($this->type, ['images', 'links'])) {
      if ($this->contentElements === null) {
        $this->contentElements = $this->toContentElements();
      }
      return $this->contentElements;
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
