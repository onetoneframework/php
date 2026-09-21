<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Markdown;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Markdown\Markdown;
use PHPUnit\Framework\TestCase;

class MarkdownTest extends TestCase
{
    private Markdown $markdown;

    protected function setUp(): void
    {
        $this->markdown = new Markdown();
    }

    // ============================================================================
    // Basic Paragraph Tests
    // ============================================================================

    public function testConvertSimpleParagraph(): void
    {
        $content = "This is a simple paragraph.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<p>This is a simple paragraph.</p>', $result);
    }

    public function testConvertMultipleParagraphs(): void
    {
        $content = "First paragraph.\n\nSecond paragraph.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<p>First paragraph.</p>', $result);
        $this->assertStringContainsString('<p>Second paragraph.</p>', $result);
    }

    public function testEmptyDocument(): void
    {
        $result = $this->markdown->convert("");
        $this->assertIsString($result);
    }

    public function testDocumentWithOnlyWhitespace(): void
    {
        $result = $this->markdown->convert("   \n\n   ");
        $this->assertIsString($result);
    }

    // ============================================================================
    // Heading Tests
    // ============================================================================

    public function testConvertAtxHeadings(): void
    {
        $content = "# H1 Heading\n## H2 Heading\n### H3 Heading";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('<h3', $result);
        $this->assertStringContainsString('H1 Heading', $result);
        $this->assertStringContainsString('H2 Heading', $result);
        $this->assertStringContainsString('H3 Heading', $result);
    }

    public function testConvertAtxHeadingLevels4Through6(): void
    {
        $content = "#### H4\n##### H5\n###### H6";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<h4', $result);
        $this->assertStringContainsString('<h5', $result);
        $this->assertStringContainsString('<h6', $result);
    }

    public function testHeadingGeneratesId(): void
    {
        $content = "# My Test Heading";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('id="', $result);
        $this->assertStringContainsString('my-test-heading', $result);
    }

    public function testSetheadingWithDuplicateIds(): void
    {
        $content = "# Heading\n\n## Heading\n\n### Heading";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('id="heading"', $result);
        $this->assertStringContainsString('id="heading-1"', $result);
    }

    public function testSetextHeadingH1(): void
    {
        $content = "Setext Heading\n===";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('Setext Heading', $result);
    }

    public function testSetextHeadingH2(): void
    {
        $content = "Setext Heading\n---";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('Setext Heading', $result);
    }

    public function testHeadingWithCustomId(): void
    {
        $content = "# My Heading {#custom-id}";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('id="custom-id"', $result);
    }

    public function testHeadingWithCustomClass(): void
    {
        $content = "# My Heading {.special}";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('class="special"', $result);
    }

    public function testHeadingWithCustomIdAndClass(): void
    {
        $content = "# My Heading {#myid .myclass}";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('id="myid"', $result);
        $this->assertStringContainsString('class="myclass"', $result);
    }

    public function testHeadingClosingHashes(): void
    {
        $content = "## Heading ##";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('Heading', $result);
        $this->assertStringNotContainsString('##', strip_tags($result));
    }

    // ============================================================================
    // Bold/Italic Tests
    // ============================================================================

    public function testConvertBold(): void
    {
        $content = "This is **bold** text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
    }

    public function testConvertBoldWithUnderscore(): void
    {
        $content = "This is __bold__ text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
    }

    public function testConvertItalic(): void
    {
        $content = "This is *italic* text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    public function testConvertItalicWithUnderscore(): void
    {
        $content = "This is _italic_ text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    public function testConvertBoldAndItalic(): void
    {
        $content = "This is ***bold and italic*** text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<strong><em>bold and italic</em></strong>', $result);
    }

    public function testConvertBoldAndItalicWithUnderscore(): void
    {
        $content = "This is ___bold and italic___ text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<strong><em>bold and italic</em></strong>', $result);
    }

    public function testNestedEmphasis(): void
    {
        $content = "***bold italic*** _**bold italic**_ **_bold italic_**";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<em>', $result);
    }

    // ============================================================================
    // Strikethrough Tests
    // ============================================================================

    public function testConvertStrikethrough(): void
    {
        $content = "This is ~~strikethrough~~ text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<del>strikethrough</del>', $result);
    }

    // ============================================================================
    // Highlight Tests
    // ============================================================================

    public function testConvertHighlight(): void
    {
        $content = "This is ==highlighted== text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<mark>highlighted</mark>', $result);
    }

    // ============================================================================
    // Inserted Text Tests
    // ============================================================================

    public function testConvertInsertedText(): void
    {
        $content = "This is ++inserted++ text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<ins>inserted</ins>', $result);
    }

    // ============================================================================
    // Subscript / Superscript Tests
    // ============================================================================

    public function testConvertSubscript(): void
    {
        $content = "H~2~O";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<sub>2</sub>', $result);
    }

    public function testConvertSuperscript(): void
    {
        $content = "x^2^";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<sup>2</sup>', $result);
    }

    // ============================================================================
    // Code Tests
    // ============================================================================

    public function testConvertInlineCode(): void
    {
        $content = "This is `inline code` here.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<code>inline code</code>', $result);
    }

    public function testConvertFencedCodeBlock(): void
    {
        $content = "```php\n\$x = 10;\necho \$x;\n```";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<pre><code', $result);
        $this->assertStringContainsString('class="language-php"', $result);
        $this->assertStringContainsString('</code></pre>', $result);
    }

    public function testConvertCodeBlockWithoutLanguage(): void
    {
        $content = "```\nNo language specified\n```";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<pre><code>', $result);
        $this->assertStringContainsString('No language specified', $result);
        $this->assertStringContainsString('</code></pre>', $result);
    }

    public function testConvertIndentedCodeBlock(): void
    {
        $content = "    indented code block\n    second line";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<pre><code>', $result);
        $this->assertStringContainsString('indented code block', $result);
    }

    public function testConvertTildeFencedCodeBlock(): void
    {
        $content = "~~~python\nprint('hello')\n~~~";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<pre><code', $result);
        $this->assertStringContainsString('class="language-python"', $result);
    }

    public function testMultipleBacktickInlineCode(): void
    {
        $content = "``code with ` backtick``";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<code>', $result);
    }

    public function testCodeBlockPreservesContent(): void
    {
        $content = "```\n*This is not* **markdown**\n```";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('*This is not*', $result);
        $this->assertStringNotContainsString('<em>This is not</em>', $result);
    }

    public function testCodeBlockWithTitle(): void
    {
        $content = "```js title=\"example.js\"\nconsole.log('hello');\n```";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('data-title="example.js"', $result);
        $this->assertStringContainsString('class="language-js"', $result);
    }

    public function testTildeCodeBlockWithTitle(): void
    {
        $content = "~~~ruby title=\"app.rb\"\nputs 'hello'\n~~~";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('data-title="app.rb"', $result);
        $this->assertStringContainsString('class="language-ruby"', $result);
    }

    // ============================================================================
    // List Tests
    // ============================================================================

    public function testConvertUnorderedList(): void
    {
        $content = "- Item 1\n- Item 2\n- Item 3";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>Item 1</li>', $result);
        $this->assertStringContainsString('<li>Item 2</li>', $result);
        $this->assertStringContainsString('<li>Item 3</li>', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    public function testConvertOrderedList(): void
    {
        $content = "1. First\n2. Second\n3. Third";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<ol', $result);
        $this->assertStringContainsString('<li>First</li>', $result);
        $this->assertStringContainsString('<li>Second</li>', $result);
        $this->assertStringContainsString('<li>Third</li>', $result);
        $this->assertStringContainsString('</ol>', $result);
    }

    public function testConvertNestedList(): void
    {
        $content = "- Item 1\n  - Nested 1\n  - Nested 2\n- Item 2";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<p>Item 1', $result);
        $this->assertStringContainsString('Nested 1', $result);
        $this->assertStringContainsString('Nested 2', $result);
    }

    public function testUnorderedListWithPlusMarker(): void
    {
        $content = "+ Item A\n+ Item B";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('Item A', $result);
    }

    public function testUnorderedListWithAsteriskMarker(): void
    {
        $content = "* Item A\n* Item B";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('Item A', $result);
    }

    public function testOrderedListWithCustomStart(): void
    {
        $content = "3. Third\n4. Fourth";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('start="3"', $result);
    }

    public function testOrderedListWithParenthesis(): void
    {
        $content = "1) First\n2) Second";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<ol', $result);
        $this->assertStringContainsString('First', $result);
    }

    // ============================================================================
    // Task List Tests
    // ============================================================================

    public function testConvertTaskList(): void
    {
        $content = "- [x] Completed task\n- [ ] Incomplete task";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('checked', $result);
        $this->assertStringContainsString('Completed task', $result);
        $this->assertStringContainsString('Incomplete task', $result);
    }

    public function testTaskListCheckboxIsDisabled(): void
    {
        $content = "- [x] Done\n- [ ] Not done";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('disabled', $result);
    }

    public function testTaskListWithUppercaseX(): void
    {
        $content = "- [X] Checked";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('checked', $result);
    }

    // ============================================================================
    // Link Tests
    // ============================================================================

    public function testConvertInlineLink(): void
    {
        $content = "[Google](https://google.com)";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<a href="https://google.com">', $result);
        $this->assertStringContainsString('Google</a>', $result);
    }

    public function testConvertLinkWithTitle(): void
    {
        $content = '[Link](https://example.com "Example")';
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('title="Example"', $result);
    }

    public function testConvertReferenceLink(): void
    {
        $content = "[Link][ref]\n\n[ref]: https://example.com";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    public function testConvertAutolink(): void
    {
        $content = "<https://example.com>";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    public function testConvertAutolinkEmail(): void
    {
        $content = "<user@example.com>";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('mailto:user@example.com', $result);
    }

    public function testConvertBareUrl(): void
    {
        $content = "Visit https://example.com for info.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    public function testCollapsedReferenceLink(): void
    {
        $content = "[example][]\n\n[example]: https://example.com";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('>example</a>', $result);
    }

    public function testShortcutReferenceLink(): void
    {
        $content = "[example]\n\n[example]: https://example.com";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    public function testReferenceLinkWithTitle(): void
    {
        $content = "[Link][ref]\n\n[ref]: https://example.com \"My Title\"";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('title="My Title"', $result);
    }

    public function testLinkWithParenthesisInUrl(): void
    {
        $content = "[Test](https://example.com/path(with)parens)";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('href=', $result);
    }

    // ============================================================================
    // Image Tests
    // ============================================================================

    public function testConvertImage(): void
    {
        $content = "Text before ![Alt text](image.jpg) text after.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="image.jpg"', $result);
        $this->assertStringContainsString('alt="Alt text"', $result);
    }

    public function testConvertImageWithDimensions(): void
    {
        $content = "Text ![Alt](image.jpg =200x100) more.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('src="image.jpg"', $result);
        $this->assertStringContainsString('width="200"', $result);
        $this->assertStringContainsString('height="100"', $result);
    }

    public function testConvertImageWithTitle(): void
    {
        $content = 'Text ![Alt](image.jpg "My Image") more.';
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('title="My Image"', $result);
    }

    public function testConvertReferenceImage(): void
    {
        $content = "Text ![Alt][img] more.\n\n[img]: https://example.com/pic.png";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="https://example.com/pic.png"', $result);
    }

    public function testCollapsedReferenceImage(): void
    {
        $content = "Text ![logo][] more.\n\n[logo]: https://example.com/logo.png";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="https://example.com/logo.png"', $result);
    }

    public function testImageWithBracketInAlt(): void
    {
        $content = "Before ![Alt with bracket](image.jpg) after.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<img', $result);
    }

    // ============================================================================
    // Standalone Image / Figure Tests
    // ============================================================================

    public function testStandaloneImageRendersAsFigure(): void
    {
        $content = "![A beautiful sunset](sunset.jpg)";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<figure>', $result);
        $this->assertStringContainsString('<img src="sunset.jpg"', $result);
        $this->assertStringContainsString('</figure>', $result);
    }

    public function testStandaloneImageWithTitleRendersFigcaption(): void
    {
        $content = '![Sunset](sunset.jpg "A beautiful sunset")';
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<figure>', $result);
        $this->assertStringContainsString('<figcaption>A beautiful sunset</figcaption>', $result);
    }

    public function testStandaloneImageWithAltRendersFigcaption(): void
    {
        $content = "![Photo caption](photo.jpg)";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<figure>', $result);
        $this->assertStringContainsString('<figcaption>Photo caption</figcaption>', $result);
    }

    public function testImageInlineWithTextNotAFigure(): void
    {
        $content = "Check this ![icon](icon.png) out.";
        $result = $this->markdown->convert($content);
        $this->assertStringNotContainsString('<figure>', $result);
        $this->assertStringContainsString('<p>', $result);
    }

    // ============================================================================
    // Blockquote Tests
    // ============================================================================

    public function testConvertBlockquote(): void
    {
        $content = "> This is a quote\n> Second line";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('This is a quote', $result);
        $this->assertStringContainsString('</blockquote>', $result);
    }

    public function testConvertNestedBlockquote(): void
    {
        $content = "> Quote level 1\n>> Quote level 2";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<blockquote>', $result);
    }

    // ============================================================================
    // Alert Tests
    // ============================================================================

    public function testConvertAlertNote(): void
    {
        $content = "> [!NOTE]\n> This is a note";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('markdown-alert-note', $result);
        $this->assertStringContainsString('Note', $result);
    }

    public function testConvertAlertWarning(): void
    {
        $content = "> [!WARNING]\n> This is a warning";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('markdown-alert-warning', $result);
        $this->assertStringContainsString('Warning', $result);
    }

    public function testConvertAlertTip(): void
    {
        $content = "> [!TIP]\n> A helpful tip";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('markdown-alert-tip', $result);
        $this->assertStringContainsString('Tip', $result);
    }

    public function testConvertAlertImportant(): void
    {
        $content = "> [!IMPORTANT]\n> Very important";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('markdown-alert-important', $result);
        $this->assertStringContainsString('Important', $result);
    }

    public function testConvertAlertCaution(): void
    {
        $content = "> [!CAUTION]\n> Be careful";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('markdown-alert-caution', $result);
        $this->assertStringContainsString('Caution', $result);
    }

    // ============================================================================
    // Table Tests
    // ============================================================================

    public function testConvertTable(): void
    {
        $content = "| Header 1 | Header 2 |\n|---|---|\n| Cell 1 | Cell 2 |";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<table>', $result);
        $this->assertStringContainsString('<thead>', $result);
        $this->assertStringContainsString('<tbody>', $result);
        $this->assertStringContainsString('Header 1', $result);
        $this->assertStringContainsString('Cell 1', $result);
    }

    public function testConvertTableWithAlignment(): void
    {
        $content = "| Left | Center | Right |\n|:---|:---:|---:|\n| L | C | R |";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('text-align: left', $result);
        $this->assertStringContainsString('text-align: center', $result);
        $this->assertStringContainsString('text-align: right', $result);
    }

    public function testTableWithEscapedPipe(): void
    {
        $content = "| Col 1 | Col 2 |\n|---|---|\n| val \\| more | end |";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<table>', $result);
    }

    public function testTableWithInlineCode(): void
    {
        $content = "| Col 1 | Col 2 |\n|---|---|\n| `code | here` | end |";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<table>', $result);
    }

    public function testTableMultipleRows(): void
    {
        $content = "| A | B |\n|---|---|\n| 1 | 2 |\n| 3 | 4 |";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<tbody>', $result);
    }

    // ============================================================================
    // Display Math Tests
    // ============================================================================

    public function testConvertDisplayMathWithDollarSigns(): void
    {
        $content = "$$\nx^2 + y^2 = z^2\n$$";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<div class="math-display"', $result);
        $this->assertStringContainsString('x^2 + y^2 = z^2', $result);
    }

    public function testConvertInlineMath(): void
    {
        $content = "The equation \$a^2 + b^2 = c^2$ is important.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<span class="math-inline"', $result);
    }

    public function testConvertDisplayMathSingleLine(): void
    {
        $content = '$$E = mc^2$$';
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<div class="math-display"', $result);
        $this->assertStringContainsString('E = mc^2', $result);
    }

    public function testConvertDisplayMathBracketNotation(): void
    {
        $content = "\\[\nx = \\frac{-b}{2a}\n\\]";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<div class="math-display"', $result);
    }

    public function testConvertInlineMathParenNotation(): void
    {
        $content = 'The formula \\(x^2\\) is quadratic.';
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<span class="math-inline"', $result);
    }

    // ============================================================================
    // Horizontal Rule Tests
    // ============================================================================

    public function testConvertHorizontalRuleDashes(): void
    {
        $content = "Text above\n\n---\n\nText below";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<hr', $result);
    }

    public function testConvertHorizontalRuleAsterisks(): void
    {
        $content = "Above\n\n***\n\nBelow";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<hr>', $result);
    }

    public function testConvertHorizontalRuleUnderscores(): void
    {
        $content = "Above\n\n___\n\nBelow";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<hr>', $result);
    }

    // ============================================================================
    // Front Matter Tests
    // ============================================================================

    public function testExtractFrontMatter(): void
    {
        $content = "---\ntitle: Test\nauthor: John\n---\n\nContent here.";
        $result = $this->markdown->convert($content);
        $frontMatter = $this->markdown->getFrontMatter();

        $this->assertNotEmpty($frontMatter);
        $this->assertArrayHasKey('title', $frontMatter);
        $this->assertEquals('Test', $frontMatter['title']);
        $this->assertArrayHasKey('author', $frontMatter);
    }

    public function testFrontMatterDoesNotAppearInContent(): void
    {
        $content = "---\ntitle: Test\n---\n\n# Main Content";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('Main Content', $result);
        $this->assertStringNotContainsString('---', $result);
    }

    public function testFrontMatterStripsQuotes(): void
    {
        $content = "---\ntitle: \"Quoted Title\"\n---\n\nHello";
        $this->markdown->convert($content);
        $fm = $this->markdown->getFrontMatter();
        $this->assertEquals('Quoted Title', $fm['title']);
    }

    // ============================================================================
    // Abbreviation Tests
    // ============================================================================

    public function testConvertAbbreviations(): void
    {
        $content = "*[HTML]: HyperText Markup Language\n\nThis is HTML.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<abbr', $result);
        $this->assertStringContainsString('title="HyperText Markup Language"', $result);
    }

    public function testMultipleAbbreviations(): void
    {
        $content = "*[CSS]: Cascading Style Sheets\n*[HTML]: HyperText Markup Language\n\nHTML and CSS are web technologies.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<abbr title="Cascading Style Sheets">CSS</abbr>', $result);
        $this->assertStringContainsString('<abbr title="HyperText Markup Language">HTML</abbr>', $result);
    }

    // ============================================================================
    // Footnote Tests
    // ============================================================================

    public function testConvertFootnotes(): void
    {
        $content = "This has a footnote[^1].\n\n[^1]: This is the footnote content.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('fnref:1', $result);
        $this->assertStringContainsString('fn:1', $result);
        $this->assertStringContainsString('footnotes', $result);
    }

    public function testFootnoteBackLink(): void
    {
        $content = "Reference[^note].\n\n[^note]: My footnote text.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&#8617;', $result);
    }

    // ============================================================================
    // Definition List Tests
    // ============================================================================

    public function testConvertDefinitionList(): void
    {
        $content = "Term\n: Definition 1\n: Definition 2";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<dl>', $result);
        $this->assertStringContainsString('<dt>Term</dt>', $result);
        $this->assertStringContainsString('<dd>Definition', $result);
    }

    public function testDefinitionListMultipleTerms(): void
    {
        $content = "First Term\n: Definition A\n\nSecond Term\n: Definition B";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<dt>First Term</dt>', $result);
        $this->assertStringContainsString('<dt>Second Term</dt>', $result);
    }

    // ============================================================================
    // Wikilink Tests
    // ============================================================================

    public function testConvertWikilink(): void
    {
        $content = "See [[My Page]].";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<a href="my-page">My Page</a>', $result);
    }

    public function testConvertWikilinkWithLabel(): void
    {
        $content = "See [[My Page|custom label]].";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<a href="my-page">custom label</a>', $result);
    }

    // ============================================================================
    // Emoji Tests
    // ============================================================================

    public function testConvertEmoji(): void
    {
        $content = "Hello :smile:!";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('😄', $result);
    }

    public function testUnknownEmojiPreserved(): void
    {
        $content = "Hello :nonexistent_emoji:!";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString(':nonexistent_emoji:', $result);
    }

    public function testMultipleEmojis(): void
    {
        $content = ":fire: :rocket: :tada:";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('🔥', $result);
        $this->assertStringContainsString('🚀', $result);
        $this->assertStringContainsString('🎉', $result);
    }

    // ============================================================================
    // Keyboard Input (kbd) Tests
    // ============================================================================

    public function testConvertKbd(): void
    {
        $content = "Press <<Ctrl>> to continue.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<kbd>Ctrl</kbd>', $result);
    }

    public function testConvertKbdCombination(): void
    {
        $content = "Press <<Ctrl>>+<<S>> to save.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<kbd>Ctrl</kbd>', $result);
        $this->assertStringContainsString('<kbd>S</kbd>', $result);
    }

    public function testKbdDoesNotConflictWithGuillemets(): void
    {
        $content = "Use <<Enter>> to submit.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<kbd>Enter</kbd>', $result);
        $this->assertStringNotContainsString('&laquo;', $result);
    }

    // ============================================================================
    // Smart Quotes Tests
    // ============================================================================

    public function testSmartDoubleQuotes(): void
    {
        $content = 'She said "hello" to him.';
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&ldquo;', $result);
        $this->assertStringContainsString('&rdquo;', $result);
    }

    public function testSmartSingleQuotes(): void
    {
        $content = "She said 'hello' to him.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&lsquo;', $result);
        $this->assertStringContainsString('&rsquo;', $result);
    }

    public function testSmartApostrophe(): void
    {
        $content = "It's a beautiful day.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&rsquo;', $result);
    }

    // ============================================================================
    // Typography Tests
    // ============================================================================

    public function testTypographyEmDash(): void
    {
        $content = "This is---important.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&mdash;', $result);
    }

    public function testTypographyEnDash(): void
    {
        $content = "Pages 10--20.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&ndash;', $result);
    }

    public function testTypographyEllipsis(): void
    {
        $content = "Wait for it...";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&hellip;', $result);
    }

    public function testTypographyCopyright(): void
    {
        $content = "Copyright (c) 2024.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&copy;', $result);
    }

    public function testTypographyRegistered(): void
    {
        $content = "Brand(r) name.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&reg;', $result);
    }

    public function testTypographyTrademark(): void
    {
        $content = "Product(tm) here.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&trade;', $result);
    }

    public function testTypographyPlusMinus(): void
    {
        $content = "Temperature is +-5 degrees.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&plusmn;', $result);
    }

    public function testTypographyFractions(): void
    {
        $content = "Use 1/2 cup and 1/4 tsp and 3/4 lb.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('&frac12;', $result);
        $this->assertStringContainsString('&frac14;', $result);
        $this->assertStringContainsString('&frac34;', $result);
    }

    public function testTypographyGuillemetsWhenNoKbd(): void
    {
        // Plain << and >> without matching kbd pattern
        $content = "He said << bonjour >>.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<p>He said <kbd> bonjour </kbd>.</p>', $result);
    }

    // ============================================================================
    // HTML Comment Tests
    // ============================================================================

    public function testHtmlCommentSingleLine(): void
    {
        $content = "Before\n\n<!-- This is a comment -->\n\nAfter";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<!-- This is a comment -->', $result);
        $this->assertStringContainsString('Before', $result);
        $this->assertStringContainsString('After', $result);
    }

    public function testHtmlCommentMultiLine(): void
    {
        $content = "Before\n\n<!--\nMulti-line\ncomment\n-->\n\nAfter";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<!--', $result);
        $this->assertStringContainsString('-->', $result);
    }

    // ============================================================================
    // Fenced Div / Container Tests
    // ============================================================================

    public function testFencedDivWithClass(): void
    {
        $content = "::: warning\nBe careful!\n:::";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<div', $result);
        $this->assertStringContainsString('class="warning"', $result);
        $this->assertStringContainsString('Be careful!', $result);
    }

    public function testFencedDivWithIdAndClass(): void
    {
        $content = "::: {#mybox .info}\nSome info\n:::";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('id="mybox"', $result);
        $this->assertStringContainsString('class="info"', $result);
    }

    public function testNestedFencedDivs(): void
    {
        $content = "::: outer\n::: inner\nNested content\n:::\n:::";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('class="outer"', $result);
        $this->assertStringContainsString('class="inner"', $result);
    }

    // ============================================================================
    // TOC Tests
    // ============================================================================

    public function testTocMarkerReplaced(): void
    {
        $content = "[TOC]\n\n# Heading One\n\n## Heading Two";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('table-of-contents', $result);
        $this->assertStringContainsString('Heading One', $result);
    }

    public function testExtractTableOfContents(): void
    {
        $content = "# Main Title\n## Section 1\n## Section 2\n### Subsection 2.1";
        $toc = $this->markdown->extractTableOfContents($content);

        $this->assertIsArray($toc);
        $this->assertCount(4, $toc);
        $this->assertEquals(1, $toc[0]['level']);
        $this->assertEquals(2, $toc[1]['level']);
        $this->assertEquals(3, $toc[3]['level']);
    }

    public function testRenderTableOfContents(): void
    {
        $content = "# Title\n## Section 1\n## Section 2";
        $toc = $this->markdown->renderTableOfContents($content);

        $this->assertIsString($toc);
        $this->assertStringContainsString('<nav', $toc);
        $this->assertStringContainsString('<ul>', $toc);
        $this->assertStringContainsString('Title', $toc);
        $this->assertStringContainsString('Section 1', $toc);
        $this->assertStringContainsString('Section 2', $toc);
    }

    public function testRenderTableOfContentsEmpty(): void
    {
        $content = "No headings here.";
        $toc = $this->markdown->renderTableOfContents($content);
        $this->assertSame('', $toc);
    }

    // ============================================================================
    // Line Break Tests
    // ============================================================================

    public function testConvertLineBreakWithTrailingSpaces(): void
    {
        $content = "Line 1  \nLine 2";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<p', $result);
    }

    public function testConvertLineBreakWithBackslash(): void
    {
        $content = "Line 1\\\nLine 2";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<br', $result);
    }

    // ============================================================================
    // Escape Tests
    // ============================================================================

    public function testEscapeSpecialCharacters(): void
    {
        $content = "This is \\*not italic\\*.";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('*not italic*', $result);
        $this->assertStringNotContainsString('<em>not italic</em>', $result);
    }

    public function testEscapeBacktick(): void
    {
        $content = "Use \\` for backticks.";
        $result = $this->markdown->convert($content);
        $this->assertStringNotContainsString('<code>', $result);
    }

    public function testEscapeHash(): void
    {
        $content = "\\# Not a heading";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('#', $result);
        $this->assertStringNotContainsString('<h1', $result);
    }

    // ============================================================================
    // HTML Block Tests
    // ============================================================================

    public function testHtmlBlockPassthrough(): void
    {
        $content = "<div class=\"custom\">\nHello\n</div>";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<div class="custom">', $result);
    }

    public function testSelfClosingHtmlBlock(): void
    {
        $content = "<hr>";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<hr>', $result);
    }

    // ============================================================================
    // Safe Mode Tests
    // ============================================================================

    public function testSafeModeSanitizesHtml(): void
    {
        $markdown = new Markdown(safeMode: true);
        $content = "Normal content.\n\n<script>alert('XSS')</script>";
        $result = $markdown->convert($content);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testSafeModeRemovesJavascriptHref(): void
    {
        $markdown = new Markdown(safeMode: true);
        $content = '<a href="javascript:alert(1)">click</a>';
        $result = $markdown->convert($content);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function testSafeModeRemovesEventHandlers(): void
    {
        $markdown = new Markdown(safeMode: true);
        $content = '<a href="#" onclick="alert(1)">click</a>';
        $result = $markdown->convert($content);
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function testSafeModeAllowsSafeTags(): void
    {
        $markdown = new Markdown(safeMode: true);
        $content = "**Bold** and *italic*.";
        $result = $markdown->convert($content);
        $this->assertStringContainsString('<strong>Bold</strong>', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    // ============================================================================
    // Text Conversion Tests
    // ============================================================================

    public function testConvertToText(): void
    {
        $content = "# Heading\n\nParagraph with **bold** and *italic*.\n\n- List item";
        $result = $this->markdown->convertToText($content);

        $this->assertIsString($result);
        $this->assertStringContainsString('Heading', $result);
        $this->assertStringContainsString('Paragraph', $result);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    // ============================================================================
    // convertInline() Tests
    // ============================================================================

    public function testConvertInlineBold(): void
    {
        $result = $this->markdown->convertInline('**bold**');
        $this->assertStringContainsString('<strong>bold</strong>', $result);
    }

    public function testConvertInlineItalic(): void
    {
        $result = $this->markdown->convertInline('*italic*');
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    public function testConvertInlineLinkMethod(): void
    {
        $result = $this->markdown->convertInline('[Google](https://google.com)');
        $this->assertStringContainsString('href="https://google.com"', $result);
    }

    public function testConvertInlineDoesNotWrapInParagraph(): void
    {
        $result = $this->markdown->convertInline('**bold** text');
        $this->assertStringNotContainsString('<p>', $result);
    }

    // ============================================================================
    // setMaxDepth() / getMaxDepth() Tests
    // ============================================================================

    public function testSetMaxDepthFluent(): void
    {
        $result = $this->markdown->setMaxDepth(5);
        $this->assertSame($this->markdown, $result);
    }

    public function testGetMaxDepthDefault(): void
    {
        $this->assertEquals(10, $this->markdown->getMaxDepth());
    }

    public function testSetMaxDepthUpdatesValue(): void
    {
        $this->markdown->setMaxDepth(3);
        $this->assertEquals(3, $this->markdown->getMaxDepth());
    }

    public function testSetMaxDepthMinimumIsOne(): void
    {
        $this->markdown->setMaxDepth(0);
        $this->assertEquals(1, $this->markdown->getMaxDepth());
    }

    // ============================================================================
    // setSafeMode() / getSafeMode() Tests
    // ============================================================================

    public function testSetSafeModeFluent(): void
    {
        $result = $this->markdown->setSafeMode(true);
        $this->assertSame($this->markdown, $result);
    }

    public function testGetSafeModeDefaultFalse(): void
    {
        $this->assertFalse($this->markdown->getSafeMode());
    }

    public function testSetSafeModeTrue(): void
    {
        $this->markdown->setSafeMode(true);
        $this->assertTrue($this->markdown->getSafeMode());
    }

    public function testSetSafeModeAffectsOutput(): void
    {
        $this->markdown->setSafeMode(true);
        $content = "Hello\n\n<script>alert('XSS')</script>";
        $result = $this->markdown->convert($content);
        $this->assertStringNotContainsString('<script>', $result);
    }

    // ============================================================================
    // getReferences() Tests
    // ============================================================================

    public function testGetReferences(): void
    {
        $content = "[ref]: https://example.com \"Title\"\n\n[Link][ref]";
        $this->markdown->convert($content);
        $refs = $this->markdown->getReferences();
        $this->assertArrayHasKey('ref', $refs);
        $this->assertEquals('https://example.com', $refs['ref']['url']);
        $this->assertEquals('Title', $refs['ref']['title']);
    }

    public function testGetReferencesEmpty(): void
    {
        $this->markdown->convert("No references here.");
        $refs = $this->markdown->getReferences();
        $this->assertEmpty($refs);
    }

    // ============================================================================
    // getFootnotes() Tests
    // ============================================================================

    public function testGetFootnotes(): void
    {
        $content = "Text[^1].\n\n[^1]: Footnote content.";
        $this->markdown->convert($content);
        $fn = $this->markdown->getFootnotes();
        $this->assertArrayHasKey('1', $fn);
        $this->assertStringContainsString('Footnote content', $fn['1']);
    }

    // ============================================================================
    // getAbbreviations() Tests
    // ============================================================================

    public function testGetAbbreviations(): void
    {
        $content = "*[HTML]: HyperText Markup Language\n\nHTML rocks.";
        $this->markdown->convert($content);
        $abbrs = $this->markdown->getAbbreviations();
        $this->assertArrayHasKey('HTML', $abbrs);
        $this->assertEquals('HyperText Markup Language', $abbrs['HTML']);
    }

    // ============================================================================
    // addReference() Tests
    // ============================================================================

    public function testAddReference(): void
    {
        $this->markdown->addReference('myref', 'https://added.com', 'Added');
        $refs = $this->markdown->getReferences();
        $this->assertArrayHasKey('myref', $refs);
        $this->assertEquals('https://added.com', $refs['myref']['url']);
    }

    public function testAddReferenceFluent(): void
    {
        $result = $this->markdown->addReference('id', 'https://url.com');
        $this->assertSame($this->markdown, $result);
    }

    // ============================================================================
    // addAbbreviation() Tests
    // ============================================================================

    public function testAddAbbreviation(): void
    {
        $this->markdown->addAbbreviation('API', 'Application Programming Interface');
        $abbrs = $this->markdown->getAbbreviations();
        $this->assertArrayHasKey('API', $abbrs);
    }

    public function testAddAbbreviationFluent(): void
    {
        $result = $this->markdown->addAbbreviation('API', 'Application Programming Interface');
        $this->assertSame($this->markdown, $result);
    }

    // ============================================================================
    // addFootnote() Tests
    // ============================================================================

    public function testAddFootnote(): void
    {
        $this->markdown->addFootnote('manual', 'Manually added footnote.');
        $fn = $this->markdown->getFootnotes();
        $this->assertArrayHasKey('manual', $fn);
        $this->assertEquals('Manually added footnote.', $fn['manual']);
    }

    public function testAddFootnoteFluent(): void
    {
        $result = $this->markdown->addFootnote('id', 'content');
        $this->assertSame($this->markdown, $result);
    }

    // ============================================================================
    // wordCount() Tests
    // ============================================================================

    public function testWordCountSimple(): void
    {
        $content = "Hello world, this is a test.";
        $count = $this->markdown->wordCount($content);
        $this->assertEquals(6, $count);
    }

    public function testWordCountWithMarkdown(): void
    {
        $content = "# Heading\n\nSome **bold** text here.";
        $count = $this->markdown->wordCount($content);
        $this->assertGreaterThan(0, $count);
    }

    public function testWordCountEmpty(): void
    {
        $count = $this->markdown->wordCount("");
        $this->assertEquals(0, $count);
    }

    public function testWordCountOnlyWhitespace(): void
    {
        $count = $this->markdown->wordCount("   \n\n   ");
        $this->assertEquals(0, $count);
    }

    // ============================================================================
    // estimateReadingTime() Tests
    // ============================================================================

    public function testEstimateReadingTimeShort(): void
    {
        $content = "Hello world.";
        $time = $this->markdown->estimateReadingTime($content);
        $this->assertEquals(1, $time);
    }

    public function testEstimateReadingTimeEmpty(): void
    {
        $time = $this->markdown->estimateReadingTime("");
        $this->assertEquals(0, $time);
    }

    public function testEstimateReadingTimeCustomWpm(): void
    {
        $words = implode(' ', array_fill(0, 200, 'word'));
        $time = $this->markdown->estimateReadingTime($words, 100);
        $this->assertEquals(2, $time);
    }

    // ============================================================================
    // extractLinks() Tests
    // ============================================================================

    public function testExtractLinksInline(): void
    {
        $content = "[Google](https://google.com) and [Bing](https://bing.com)";
        $links = $this->markdown->extractLinks($content);
        $this->assertCount(2, $links);
        $this->assertEquals('https://google.com', $links[0]['url']);
        $this->assertEquals('Google', $links[0]['text']);
    }

    public function testExtractLinksWithTitle(): void
    {
        $content = '[Link](https://example.com "Title")';
        $links = $this->markdown->extractLinks($content);
        $this->assertCount(1, $links);
        $this->assertEquals('Title', $links[0]['title']);
    }

    public function testExtractLinksEmpty(): void
    {
        $content = "No links here.";
        $links = $this->markdown->extractLinks($content);
        $this->assertEmpty($links);
    }

    // ============================================================================
    // extractImages() Tests
    // ============================================================================

    public function testExtractImages(): void
    {
        $content = "![Photo](photo.jpg) and ![Logo](logo.png)";
        $images = $this->markdown->extractImages($content);
        $this->assertCount(2, $images);
        $this->assertEquals('photo.jpg', $images[0]['url']);
        $this->assertEquals('Photo', $images[0]['alt']);
    }

    public function testExtractImagesWithTitle(): void
    {
        $content = '![Alt](img.jpg "My Title")';
        $images = $this->markdown->extractImages($content);
        $this->assertCount(1, $images);
        $this->assertEquals('My Title', $images[0]['title']);
    }

    public function testExtractImagesEmpty(): void
    {
        $content = "No images here.";
        $images = $this->markdown->extractImages($content);
        $this->assertEmpty($images);
    }

    // ============================================================================
    // Edge Cases
    // ============================================================================

    public function testHandlesWindowsLineEndings(): void
    {
        $content = "Para 1\r\n\r\nPara 2";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('Para 1', $result);
        $this->assertStringContainsString('Para 2', $result);
    }

    public function testHandlesMacLineEndings(): void
    {
        $content = "Para 1\r\rPara 2";
        $result = $this->markdown->convert($content);
        $this->assertIsString($result);
    }

    public function testDeeplyNestedBlockquotesHitLimit(): void
    {
        $content = str_repeat('> ', 15) . 'Deep';
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('Deep', $result);
    }

    public function testVeryLongParagraph(): void
    {
        $content = str_repeat('word ', 1000);
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<p>', $result);
    }

    public function testSpecialCharactersInHeading(): void
    {
        $content = "# Hello & World <Goodbye>";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<h1', $result);
    }

    public function testCodeBlockInsideBlockquote(): void
    {
        $content = "> ```\n> code here\n> ```";
        $result = $this->markdown->convert($content);
        $this->assertStringContainsString('<blockquote>', $result);
    }

    // ============================================================================
    // Complex Multi-Feature Tests
    // ============================================================================

    public function testComplexDocument(): void
    {
        $content = <<<'MD'
# My Document

This is a paragraph with **bold** and *italic* text.

> A quote with `code`

## Section with List

- Item 1
- Item 2
  - Nested item

```php
<?php echo "Hello";
```

| Header 1 | Header 2 |
|---|---|
| Cell 1 | Cell 2 |
MD;
        $result = $this->markdown->convert($content);

        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<pre><code', $result);
        $this->assertStringContainsString('<table>', $result);
    }

    public function testComplexDocumentWithExtendedFeatures(): void
    {
        $content = <<<'MD'
---
title: Extended Test
---

# Extended Features {#ext}

This has a footnote[^fn1] and ==highlights== and ~~deletions~~.

Press <<Ctrl>>+<<C>> to copy.

Term
: Definition here

::: note
A container div
:::

*[API]: Application Programming Interface

The API is great.

[^fn1]: This is the footnote.
MD;
        $result = $this->markdown->convert($content);

        $this->assertStringContainsString('id="ext"', $result);
        $this->assertStringContainsString('<mark>highlights</mark>', $result);
        $this->assertStringContainsString('<del>deletions</del>', $result);
        $this->assertStringContainsString('<kbd>Ctrl</kbd>', $result);
        $this->assertStringContainsString('<dl>', $result);
        $this->assertStringContainsString('class="note"', $result);
        $this->assertStringContainsString('<abbr', $result);
        $this->assertStringContainsString('footnotes', $result);

        $fm = $this->markdown->getFrontMatter();
        $this->assertEquals('Extended Test', $fm['title']);
    }

    public function testFluentApiChaining(): void
    {
        $result = $this->markdown
            ->setSafeMode(false)
            ->setMaxDepth(5)
            ->addReference('site', 'https://example.com')
            ->addAbbreviation('URL', 'Uniform Resource Locator')
            ->addFootnote('1', 'A note');

        $this->assertSame($this->markdown, $result);
        $this->assertFalse($this->markdown->getSafeMode());
        $this->assertEquals(5, $this->markdown->getMaxDepth());
    }

    public function testConvertResetsState(): void
    {
        $this->markdown->convert("[ref]: https://first.com\n\n[ref]");
        $refs1 = $this->markdown->getReferences();
        $this->assertArrayHasKey('ref', $refs1);

        $this->markdown->convert("No references.");
        $refs2 = $this->markdown->getReferences();
        $this->assertEmpty($refs2);
    }

    public function testConvertFromHtml(): void
    {
        $md = new Markdown();
        $this->assertEquals($md->convertFromHtml('<h1>Hello</h1>'), '# Hello');
        $this->assertEquals($md->convertFromHtml('<h3>Sub</h3>'), '### Sub');
        $this->assertEquals($md->convertFromHtml('<h1>Hello</h1>', ['heading_style' => 'setext']), "Hello\n=====");
        $this->assertEquals($md->convertFromHtml('<h2>World</h2>', ['heading_style' => 'setext']), "World\n-----");
        $this->assertEquals($md->convertFromHtml('<p><strong>bold</strong></p>'), '**bold**');
        $this->assertEquals($md->convertFromHtml('<p><em>italic</em></p>'), '*italic*');
        $this->assertEquals($md->convertFromHtml('<p><strong><em>bi</em></strong></p>'), '***bi***');
        $this->assertEquals($md->convertFromHtml('<p><del>del</del></p>'), '~~del~~');
        $this->assertEquals($md->convertFromHtml('<p><mark>hi</mark></p>'), '==hi==');
        $this->assertEquals($md->convertFromHtml('<p><ins>ins</ins></p>'), '++ins++');
        $this->assertEquals($md->convertFromHtml('<p><sub>2</sub></p>'), '~2~');
        $this->assertEquals($md->convertFromHtml('<p><sup>2</sup></p>'), '^2^');
        $this->assertEquals($md->convertFromHtml('<p><kbd>Ctrl</kbd></p>'), '<<Ctrl>>');
        $this->assertEquals($md->convertFromHtml('<p><code>foo()</code></p>'), '`foo()`');
        $this->assertEquals($md->convertFromHtml('<p><strong>x</strong></p>', ['strong_em_symbol' => '_']), '__x__');
        $this->assertEquals($md->convertFromHtml('<a href="https://example.com">Example</a>'), '[Example](https://example.com)');
        $this->assertEquals($md->convertFromHtml('<a href="/p" title="T">Page</a>'), '[Page](/p "T")');
        $this->assertEquals($md->convertFromHtml('<a href="https://x.com">https://x.com</a>'), '<https://x.com>');
        $this->assertEquals($md->convertFromHtml('<img src="/img.png" alt="Alt">'), '![Alt](/img.png)');
        $this->assertEquals($md->convertFromHtml('<img src="/a.png" alt="A" title="T">'), '![A](/a.png "T")');
        $this->assertEquals($md->convertFromHtml('<img src="/a.png" alt="A" width="100" height="50">'), '![A](/a.png =100x50)');
        $this->assertEquals($md->convertFromHtml('<pre><code>echo 1;</code></pre>'), "```\necho 1;\n```");
        $this->assertEquals($md->convertFromHtml('<pre><code class="language-php">echo 1;</code></pre>'), "```php\necho 1;\n```");
        $this->assertEquals($md->convertFromHtml('<ul><li>A</li><li>B</li></ul>'), "- A\n- B");
        $this->assertEquals($md->convertFromHtml('<ol><li>First</li><li>Second</li></ol>'), "1. First\n2. Second");
        $this->assertEquals($md->convertFromHtml('<ul><li>A<ul><li>A1</li></ul></li><li>B</li></ul>'), "- A\n    - A1\n- B");
        $this->assertEquals($md->convertFromHtml('<ul><li><input type="checkbox" checked> Done</li></ul>'), '- [x] Done');
        $this->assertEquals($md->convertFromHtml('<ul><li><input type="checkbox"> Todo</li></ul>'), '- [ ] Todo');
        $this->assertEquals($md->convertFromHtml('<blockquote><p>Quote</p></blockquote>'), "> Quote");
        $this->assertEquals($md->convertFromHtml('<hr>'), '---');
        $tableHtml = '<table><thead><tr><th>Name</th><th style="text-align:right">Score</th></tr></thead><tbody><tr><td>Alice</td><td>95</td></tr><tr><td>Bob</td><td>88</td></tr></tbody></table>';
        $expected = "| Name | Score |\n| ---- | ----: |\n| Alice | 95 |\n| Bob | 88 |";
        $this->assertEquals($md->convertFromHtml($tableHtml), $expected);
        $this->assertEquals($md->convertFromHtml('<p>안녕하세요</p>'), '안녕하세요');
        $this->assertEquals($md->convertFromHtml('<p>© 2025</p>'), '© 2025');
        $this->assertEquals($md->convertFromHtml('<p>line1<br>line2</p>', ['newline_style' => 'spaces']), "line1  \nline2");
        $this->assertEquals($md->convertFromHtml('<p>line1<br>line2</p>', ['newline_style' => 'backslash']), "line1\\\nline2");
    }
}
