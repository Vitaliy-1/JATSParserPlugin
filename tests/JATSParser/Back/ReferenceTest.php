<?php

namespace JATSParser\Tests\Back;

use PHPUnit\Framework\TestCase;
use JATSParser\Body\Document;
use JATSParser\Back\Journal;
use JATSParser\Back\Book;
use JATSParser\Back\Chapter;
use JATSParser\Back\Conference;

class ReferenceTest extends TestCase
{
    private static string $fixturesPath;
    private static Document $doc;

    public static function setUpBeforeClass(): void
    {
        self::$fixturesPath = dirname(__DIR__, 2) . '/fixtures';
        self::$doc = new Document(self::$fixturesPath . '/references.xml');
    }

    public function testCorrectNumberOfReferences(): void
    {
        $refs = self::$doc->getReferences();
        $this->assertCount(4, $refs, 'Article should have 4 references');
    }

    public function testJournalReferenceType(): void
    {
        $refs = self::$doc->getReferences();
        $this->assertInstanceOf(Journal::class, $refs[0]);
    }

    public function testBookReferenceType(): void
    {
        $refs = self::$doc->getReferences();
        $this->assertInstanceOf(Book::class, $refs[1]);
    }

    public function testChapterReferenceType(): void
    {
        $refs = self::$doc->getReferences();
        $this->assertInstanceOf(Chapter::class, $refs[2]);
    }

    public function testConferenceReferenceType(): void
    {
        $refs = self::$doc->getReferences();
        // confproc is not mapped; parser falls back to Journal
        $this->assertInstanceOf(Journal::class, $refs[3]);
    }

    // ─── getRawReference fix ─────────────────────────────────────────

    public function testGetRawReferenceReturnsContentNotLabel(): void
    {
        $refs = self::$doc->getReferences();

        $rawRef = $refs[0]->getRawReference();

        // The raw reference should contain the citation text, NOT just the label "1"
        $this->assertStringContainsString('Smith', $rawRef, 'Raw reference should contain author name');
        $this->assertStringContainsString('groundbreaking study', $rawRef, 'Raw reference should contain article title');
        $this->assertStringContainsString('2023', $rawRef, 'Raw reference should contain year');
    }

    public function testGetRawReferenceNotEmpty(): void
    {
        $refs = self::$doc->getReferences();

        foreach ($refs as $index => $ref) {
            $rawRef = $ref->getRawReference();
            $this->assertNotEmpty($rawRef, "Reference #{$index} getRawReference() should not be empty");
            // Should be longer than just a number label
            $this->assertGreaterThan(5, strlen($rawRef),
                "Reference #{$index} getRawReference() should contain actual citation text, not just a label");
        }
    }

    // ─── Mixed vs element citations ──────────────────────────────────

    public function testMixedCitationDetected(): void
    {
        $refs = self::$doc->getReferences();
        // ref-1 is mixed-citation
        $this->assertTrue($refs[0]->isMixed(), 'Reference 1 should be detected as mixed-citation');
    }

    public function testElementCitationDetected(): void
    {
        $refs = self::$doc->getReferences();
        // ref-2 is element-citation
        $this->assertFalse($refs[1]->isMixed(), 'Reference 2 should be detected as element-citation');
    }

    // ─── Reference metadata ──────────────────────────────────────────

    public function testReferenceId(): void
    {
        $refs = self::$doc->getReferences();
        $this->assertEquals('ref-1', $refs[0]->getId());
        $this->assertEquals('ref-2', $refs[1]->getId());
    }

    public function testReferenceYear(): void
    {
        $refs = self::$doc->getReferences();
        $this->assertEquals('2023', $refs[0]->getYear());
        $this->assertEquals('2022', $refs[1]->getYear());
    }

    public function testReferenceAuthors(): void
    {
        $refs = self::$doc->getReferences();
        $authors = $refs[0]->getAuthors();

        $this->assertCount(2, $authors, 'Reference 1 should have 2 authors');
    }

    public function testReferenceEditors(): void
    {
        $refs = self::$doc->getReferences();
        // ref-3 (chapter) has an editor
        $editors = $refs[2]->getEditors();

        $this->assertCount(1, $editors, 'Reference 3 should have 1 editor');
    }

    public function testReferenceDoi(): void
    {
        $refs = self::$doc->getReferences();
        $pubIds = $refs[0]->getPubIdType();

        $this->assertArrayHasKey('doi', $pubIds);
        $this->assertStringContainsString('10.1234', $pubIds['doi']);
    }

    public function testReferencePmid(): void
    {
        $refs = self::$doc->getReferences();
        $pubIds = $refs[3]->getPubIdType();

        $this->assertArrayHasKey('pmid', $pubIds);
        $this->assertStringContainsString('12345678', $pubIds['pmid']);
    }

    // ─── Collaboration authors ───────────────────────────────────────

    public function testCollaborationAuthorDetected(): void
    {
        $refs = self::$doc->getReferences();
        // ref-4 has a collab author "XML Working Group"
        $authors = $refs[3]->getAuthors();

        $this->assertGreaterThanOrEqual(2, count($authors),
            'Reference 4 should have individual + collaboration authors');
    }
}
