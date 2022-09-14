<?php namespace JATSParser\Body;

use JATSParser\Body\Document as Document;
use JATSParser\Body\Text as Text;

class Verse extends AbstractElement {

	private $content = array();
	private $attrib;

	public function __construct(\DOMElement $element) {
		parent::__construct($element);

		$verseItemNodes = $this->xpath->query("verse-line", $element);
		foreach ($verseItemNodes as $verseItemNode) {
			$verseItem = $this->extractFormattedText(".", $verseItemNode);
			$this->content[] = $verseItem;
		}

		$this->attrib = $this->extractFormattedText(".//attrib", $element);

	}

	public function getContent(): array {
		return $this->content;
	}

	public function getAttrib(): array {
		return $this->attrib;
	}
}
