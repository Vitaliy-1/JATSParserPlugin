<?php namespace JATSParser\Body;

use JATSParser\Body\Document as Document;

/**
 * Represents a JATS <inline-graphic> element (an image embedded inline in text flow).
 */
class InlineGraphic implements JATSElement {

    /** @var string xlink:href value (filename/path of the image) */
    private string $href;

    public function __construct(\DOMElement $element) {
        $xpath = Document::getXpath();
        $hrefNodes = $xpath->query('./@xlink:href', $element);
        $this->href = ($hrefNodes->length > 0) ? $hrefNodes->item(0)->nodeValue : '';
    }

    public function getHref(): string {
        return $this->href;
    }

    public function getContent(): array {
        return [];
    }
}
