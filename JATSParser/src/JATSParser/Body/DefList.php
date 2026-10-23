<?php namespace JATSParser\Body;

class DefList extends AbstractElement {

	private $items = [];
	private $title = null;

	public function __construct(\DOMElement $element) {
		parent::__construct($element);

		$titleNodes = $this->xpath->query("title", $element);
		if ($titleNodes->length > 0) {
			$this->title = $titleNodes->item(0)->textContent;
		}

		$defItems = $this->xpath->query("def-item", $element);
		foreach ($defItems as $defItem) {
			$termNodes = $this->xpath->query(".//term//text()", $defItem);
			$termTexts = [];
			foreach ($termNodes as $termNode) {
				$termTexts[] = new Text($termNode);
			}

			$defNodes = $this->xpath->query(".//def//text()", $defItem);
			$defTexts = [];
			foreach ($defNodes as $defNode) {
				$defTexts[] = new Text($defNode);
			}

			$this->items[] = ['term' => $termTexts, 'def' => $defTexts];
		}
	}

	public function getContent(): array {
		return $this->items;
	}

	public function getTitle(): ?string {
		return $this->title;
	}
}
