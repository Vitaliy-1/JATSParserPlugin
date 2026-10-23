<?php

namespace JATSParser\Tests\Body;

use PHPUnit\Framework\TestCase;
use JATSParser\Body\Document;
use JATSParser\Body\Section;
use JATSParser\Body\Par;
use JATSParser\Body\BoxedText;
use JATSParser\Body\DefList;
use JATSParser\Body\Preformat;
use JATSParser\Body\DispFormula;
use JATSParser\Body\DispQuote;
use JATSParser\Body\Text;
use JATSParser\Body\InlineGraphic;

class DocumentTest extends TestCase
{
    private static string $fixturesPath;

    public static function setUpBeforeClass(): void
    {
        self::$fixturesPath = dirname(__DIR__, 2) . '/fixtures';
    }

    // ─── Basic structure ─────────────────────────────────────────────

    public function testBasicArticleParsesCorrectNumberOfSections(): void
    {
        $doc = new Document(self::$fixturesPath . '/basic-article.xml');
        $sections = $doc->getArticleSections();

        $this->assertCount(2, $sections, 'Article should have 2 top-level sections');
    }

    public function testBasicArticleSectionTitles(): void
    {
        $doc = new Document(self::$fixturesPath . '/basic-article.xml');
        $sections = $doc->getArticleSections();

        $this->assertInstanceOf(Section::class, $sections[0]);
        $this->assertEquals('Introduction', $sections[0]->getTitle());
        $this->assertEquals('Methods', $sections[1]->getTitle());
    }

    public function testBasicArticleSectionContent(): void
    {
        $doc = new Document(self::$fixturesPath . '/basic-article.xml');
        $sections = $doc->getArticleSections();

        $content = $sections[0]->getContent();
        // Section 1 has 2 paragraphs
        $parasOnly = array_filter($content, fn($el) => $el instanceof Par);
        $this->assertCount(2, $parasOnly, 'Introduction should have 2 paragraphs');
    }

    public function testBasicArticleReferences(): void
    {
        $doc = new Document(self::$fixturesPath . '/basic-article.xml');
        $refs = $doc->getReferences();

        $this->assertCount(1, $refs, 'Article should have 1 reference');
    }

    // ─── Inline tags (Body\Text) ─────────────────────────────────────

    public function testInlineTagsParseWithoutErrors(): void
    {
        $doc = new Document(self::$fixturesPath . '/inline-tags.xml');
        $sections = $doc->getArticleSections();

        $this->assertCount(1, $sections);
        $content = $sections[0]->getContent();
        // 16 paragraphs in the fixture
        $parasOnly = array_filter($content, fn($el) => $el instanceof Par);
        $this->assertGreaterThanOrEqual(14, count($parasOnly));
    }

    public function testInlineGraphicParsedFromParagraph(): void
    {
        $doc = new Document(self::$fixturesPath . '/inline-tags.xml');
        $sections = $doc->getArticleSections();
        $content = $sections[0]->getContent();

        // Find paragraphs that contain InlineGraphic elements
        $foundInlineGraphic = false;
        foreach ($content as $element) {
            if ($element instanceof Par) {
                foreach ($element->getContent() as $child) {
                    if ($child instanceof InlineGraphic) {
                        $foundInlineGraphic = true;
                        $this->assertEquals('figure1.png', $child->getHref());
                    }
                }
            }
        }

        $this->assertTrue($foundInlineGraphic, 'An InlineGraphic should be found in the parsed content');
    }

    public function testBoldTextHasBoldType(): void
    {
        $doc = new Document(self::$fixturesPath . '/inline-tags.xml');
        $sections = $doc->getArticleSections();
        $content = $sections[0]->getContent();

        // Paragraph "Combined <bold><italic>bold italic</italic></bold> text."
        // Look for a Text node with both bold and italic types
        $foundBoldItalic = false;
        foreach ($content as $element) {
            if ($element instanceof Par) {
                foreach ($element->getContent() as $child) {
                    if ($child instanceof Text) {
                        $types = $child->getType();
                        if (in_array('bold', $types) && in_array('italic', $types)) {
                            $foundBoldItalic = true;
                            $this->assertEquals('bold italic', $child->getContent());
                        }
                    }
                }
            }
        }

        $this->assertTrue($foundBoldItalic, 'Should find a text node with both bold and italic types');
    }

    // ─── Block elements ──────────────────────────────────────────────

    public function testBoxedTextParsed(): void
    {
        $doc = new Document(self::$fixturesPath . '/block-elements.xml');
        $sections = $doc->getArticleSections();
        $content = $sections[0]->getContent();

        $boxedTexts = array_filter($content, fn($el) => $el instanceof BoxedText);
        $this->assertCount(1, $boxedTexts, 'Should find 1 boxed-text element');
    }

    public function testDefListParsed(): void
    {
        $doc = new Document(self::$fixturesPath . '/block-elements.xml');
        $sections = $doc->getArticleSections();
        $content = $sections[0]->getContent();

        $defLists = array_values(array_filter($content, fn($el) => $el instanceof DefList));
        $this->assertCount(1, $defLists, 'Should find 1 def-list element');

        $defList = $defLists[0];
        $this->assertEquals('Glossary', $defList->getTitle());

        $items = $defList->getContent();
        $this->assertCount(2, $items, 'Def-list should have 2 items');

        // Verify first item term text
        $firstTermTexts = $items[0]['term'];
        $this->assertNotEmpty($firstTermTexts);
        $termContent = '';
        foreach ($firstTermTexts as $t) {
            $termContent .= $t->getContent();
        }
        $this->assertEquals('API', $termContent);
    }

    public function testPreformatParsed(): void
    {
        $doc = new Document(self::$fixturesPath . '/block-elements.xml');
        $sections = $doc->getArticleSections();
        $content = $sections[0]->getContent();

        $preformats = array_values(array_filter($content, fn($el) => $el instanceof Preformat));
        // Fixture has both <preformat> and <code> block
        $this->assertCount(2, $preformats, 'Should find 2 preformat/code block elements');

        $this->assertStringContainsString('function hello', $preformats[0]->getContent());
        $this->assertEquals('preformat', $preformats[0]->getContentType());

        $this->assertStringContainsString('SELECT', $preformats[1]->getContent());
        $this->assertEquals('code', $preformats[1]->getContentType());
    }

    public function testDispFormulaParsed(): void
    {
        $doc = new Document(self::$fixturesPath . '/block-elements.xml');
        $sections = $doc->getArticleSections();
        $content = $sections[0]->getContent();

        $formulas = array_values(array_filter($content, fn($el) => $el instanceof DispFormula));
        $this->assertCount(2, $formulas, 'Should find 2 disp-formula elements');

        $this->assertEquals('eq-1', $formulas[0]->getId());
        $this->assertEquals('(1)', $formulas[0]->getLabel());
        $this->assertTrue($formulas[0]->hasLatex());
        $this->assertEquals('E = mc^2', $formulas[0]->getTexContent());

        $this->assertEquals('eq-2', $formulas[1]->getId());
        $this->assertStringContainsString('sum_', $formulas[1]->getTexContent());
    }

    public function testDispQuoteParsed(): void
    {
        $doc = new Document(self::$fixturesPath . '/block-elements.xml');
        $sections = $doc->getArticleSections();
        $content = $sections[0]->getContent();

        $quotes = array_values(array_filter($content, fn($el) =>
            $el instanceof DispQuote && !$el instanceof BoxedText
        ));
        $this->assertCount(1, $quotes, 'Should find 1 disp-quote element');
    }
}
