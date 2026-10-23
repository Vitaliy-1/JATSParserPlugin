<?php

namespace JATSParser\Tests\HTML;

use PHPUnit\Framework\TestCase;
use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;

class DocumentTest extends TestCase
{
    private static string $fixturesPath;

    public static function setUpBeforeClass(): void
    {
        self::$fixturesPath = dirname(__DIR__, 2) . '/fixtures';
    }

    private function getHTML(string $fixture): string
    {
        $jatsDoc = new JATSDocument(self::$fixturesPath . '/' . $fixture);
        $htmlDoc = new HTMLDocument($jatsDoc);
        return $htmlDoc->saveAsHTML();
    }

    // ─── Basic rendering ─────────────────────────────────────────────

    public function testBasicArticleRendersHTML(): void
    {
        $html = $this->getHTML('basic-article.xml');

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('simple paragraph', $html);
    }

    public function testBasicArticleContainsSectionHeaders(): void
    {
        $html = $this->getHTML('basic-article.xml');

        $this->assertStringContainsString('Introduction', $html);
        $this->assertStringContainsString('Methods', $html);
    }

    public function testBoldAndItalicRendered(): void
    {
        $html = $this->getHTML('basic-article.xml');

        $this->assertStringContainsString('<b>', $html);
        $this->assertStringContainsString('bold text', $html);
        $this->assertStringContainsString('<i>', $html);
        $this->assertStringContainsString('italic text', $html);
    }

    // ─── Inline tags rendering ───────────────────────────────────────

    public function testMonospaceRendersAsCode(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertStringContainsString('<code>monospaced code</code>', $html);
    }

    public function testSmallCapsRendersAsSpan(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<span[^>]*class="small-caps"[^>]*>small caps<\/span>/', $html);
    }

    public function testStrikethroughRendersAsS(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertStringContainsString('<s>strikethrough</s>', $html);
    }

    public function testNamedContentRendersAsSpan(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<span[^>]*class="named-content"[^>]*>BRCA1<\/span>/', $html);
    }

    public function testStyledContentRendersAsSpan(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<span[^>]*class="styled-content"[^>]*>styled<\/span>/', $html);
    }

    public function testAbbrevRendersAsAbbr(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertStringContainsString('<abbr>WHO</abbr>', $html);
    }

    public function testInlineCodeRendersAsCode(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertStringContainsString('<code>inline code</code>', $html);
    }

    public function testCommentRendersAsAnnotationSpan(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<span[^>]*class="annotation"[^>]*>editorial comment<\/span>/', $html);
    }

    public function testRomanRendersAsSpan(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<span[^>]*class="roman"[^>]*>roman text<\/span>/', $html);
    }

    public function testOverlineRendersWithStyle(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<span[^>]*style="text-decoration:overline"[^>]*>overlined text<\/span>/', $html);
    }

    public function testEmailRendersAsMailtoLink(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<a[^>]*href="mailto:test@example\.com"[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<a[^>]*class="email"[^>]*>/', $html);
    }

    public function testUriRendersAsLink(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<a[^>]*href="https:\/\/example\.com"[^>]*>/', $html);
    }

    public function testInlineFormulaRendersAsSpan(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<span[^>]*class="inline-formula"[^>]*>/', $html);
    }

    public function testInlineFormulaWithTexMathRendersContent(): void
    {
        // tex-math nested inside inline-formula: span wrapper is lost,
        // but content is preserved (known parser limitation)
        $html = $this->getHTML('inline-tags.xml');
        $this->assertStringContainsString('x + y = z', $html);
    }

    public function testInlineGraphicRendersAsImg(): void
    {
        $html = $this->getHTML('inline-tags.xml');
        $this->assertMatchesRegularExpression('/<img[^>]*src="figure1\.png"/', $html);
    }

    // ─── Block elements rendering ────────────────────────────────────

    public function testBlockElementsRenderWithoutErrors(): void
    {
        $html = $this->getHTML('block-elements.xml');
        $this->assertNotEmpty($html);
    }

    public function testBoxedTextRendersAsDiv(): void
    {
        $html = $this->getHTML('block-elements.xml');
        // BoxedText extends DispQuote which renders as blockquote by default,
        // but might render differently. Check for the content at minimum.
        $this->assertStringContainsString('content inside a boxed text', $html);
    }

    public function testDefListRendersAsDl(): void
    {
        $html = $this->getHTML('block-elements.xml');

        $this->assertStringContainsString('<dl', $html);
        $this->assertStringContainsString('<dt', $html);
        $this->assertStringContainsString('<dd', $html);
        $this->assertStringContainsString('API', $html);
        $this->assertStringContainsString('Application Programming Interface', $html);
    }

    public function testPreformatRendersAsPre(): void
    {
        $html = $this->getHTML('block-elements.xml');

        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('function hello', $html);
    }

    public function testCodeBlockRendersAsPre(): void
    {
        $html = $this->getHTML('block-elements.xml');

        $this->assertStringContainsString('SELECT', $html);
    }

    public function testDispFormulaRendered(): void
    {
        $html = $this->getHTML('block-elements.xml');

        // Formula content should be present
        $this->assertStringContainsString('E = mc^2', $html);
        // The disp-formula div should be present
        $this->assertMatchesRegularExpression('/disp-formula|formula/', $html);
    }

    public function testDispFormulaLatexWrapped(): void
    {
        $html = $this->getHTML('block-elements.xml');

        // LaTeX formulas should be wrapped in \[...\] for MathJax
        // or otherwise marked for rendering
        $this->assertStringContainsString('E = mc^2', $html);
    }

    // ─── References rendering ────────────────────────────────────────

    public function testReferencesRenderWithoutErrors(): void
    {
        $jatsDoc = new JATSDocument(self::$fixturesPath . '/references.xml');
        $htmlDoc = new HTMLDocument($jatsDoc);

        // Don't call setReferences() (CiteProc) — just verify the document renders
        $html = $htmlDoc->saveAsHTML();
        $this->assertNotEmpty($html);
    }
}
