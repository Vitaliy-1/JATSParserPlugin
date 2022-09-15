<?php namespace JATSParser\Body;

use JATSParser\Body\JATSElement as JATSElement;
use JATSParser\Body\Document as Document;
use JATSParser\Body\Text as Text;

class Par implements JATSElement {

	private $content = array();
	private $blockElements = array();

	function __construct(\DOMElement $paragraph) {
		$xpath = Document::getXpath();

		// Find, set and exclude block elements from DOM
		$this->findExtractRemoveBlockElements($paragraph, $xpath);

		// Parse content
		$content = array();
		$parTextNodes = $xpath->query(".//text()", $paragraph);
		foreach ($parTextNodes as $parTextNode) {
			$jatsText = new Text($parTextNode);
			$content[] = $jatsText;
		}
		$this->content = $content;
	}

	public function getContent(): array {
		return $this->content;
	}

	public function getBlockElements() {
		return $this->blockElements;
	}

	/**
	 * @param \DOMElement $paragraph
	 * @param \DOMXPath $xpath
	 * @brief Method aimed at finding block elements inside the paragraph, save as an array property and delete them from the DOM
	 */
	private function findExtractRemoveBlockElements(\DOMElement $paragraph, \DOMXPath $xpath): void
	{
		$expression = "";
		$blockNodesMappedArray = AbstractElement::mappedBlockElements();
		$lastKey = array_key_last($blockNodesMappedArray);
		foreach ($blockNodesMappedArray  as $key => $nodeString) {
			$expression .= ".//" . $nodeString;
			if ($key !== $lastKey) {
				$expression .= "|";
			}
		}

		$blockElements = $xpath->query($expression, $paragraph);
		if (empty($blockElements)) return;

		foreach ($blockElements as $blockElement) {
			if ($className = array_search($blockElement->tagName, $blockNodesMappedArray)) {
				$className = "JATSParser\Body\\" . $className;
				$jatsBlockEl = new $className($blockElement);
				$this->blockElements[] = $jatsBlockEl;
			}

			$blockElement->parentNode->removeChild($blockElement);
		}

	}
}
