<?php namespace JATSParser\HTML;

use JATSParser\Body\Verse as JATSVerse;
use JATSParser\HTML\Text as HTMLText;

class Verse extends \DOMElement {

	public function __construct() {
		parent::__construct("p");
	}

	public function setContent(JATSVerse $jatsVerse) {
		if (!empty($jatsVerse->getContent())) {
			foreach ($jatsVerse->getContent() as $item) {
				foreach ($item as $text) {
					HTMLText::extractText($text, $this);
				}
				$this->appendChild($this->ownerDocument->createElement("br"));
			}
		}

		if (!empty($attribTexts = $jatsVerse->getAttrib())) {
			$citeElement = $this->ownerDocument->createElement("cite");
			$this->appendChild($citeElement);
			foreach ($attribTexts as $attribText) {
				Text::extractText($attribText, $citeElement);
			}
		}
	}
}
