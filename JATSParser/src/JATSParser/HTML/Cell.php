<?php namespace JATSParser\HTML;

use JATSParser\Body\Cell as JATSCell;
use JATSParser\Body\Text as JATSText;
use JATSParser\HTML\Text as HTMLText;

class Cell extends \DOMElement {

	public function __construct(string $type) {

		parent::__construct($type);

	}

	public function setContent(JATSCell $cell) {

		if ($cell->getColspan()) {
			$this->setAttribute("colspan", $cell->getColspan());
		}


		if ($cell->getRowspan()) {
			$this->setAttribute("rowspan", $cell->getRowspan());
		}

		// set some style

		if ($cell->getColspan() > 1) {
			$this->setAttribute("align", "center");
		}

		foreach ($cell->getContent() as $cellContents) {
			switch (get_class($cellContents)) {
				case "JATSParser\Body\Par":
					$par = new Par();
					$this->appendChild($par);
					$par->setContent($cellContents);
					break;
				case "JATSParser\Body\Text":
					HTMLText::extractText($cellContents, $this);
					break;
			}
		}
	}
}