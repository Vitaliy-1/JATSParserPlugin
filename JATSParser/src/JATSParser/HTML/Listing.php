<?php namespace JATSParser\HTML;

use JATSParser\Body\Listing as JATSListing;
use JATSParser\Body\Text as JATSText;
use JATSParser\Body\Par as JATSPar;
use JATSParser\HTML\Par as Par;
use JATSParser\HTML\Text as HTMLText;

class Listing extends \DOMElement {

	public function __construct(string $type) {

		($type === "unordered" || $type === "bullet") ? parent::__construct("ul") : parent::__construct("ol");

	}

	public function setContent(JATSListing $jatsListing) {

		foreach ($jatsListing->getContent() as $jatsListItem) {
			$listItem = $this->ownerDocument->createElement("li");
			$this->appendChild($listItem);
			foreach ($jatsListItem as $jatsListText) {
				if (get_class($jatsListText) === "JATSParser\Body\Text") {
					HTMLText::extractText($jatsListText, $listItem);
				} elseif (get_class($jatsListText) === "JATSParser\Body\Listing") {
					/* @var $jatsListText JATSListing */
					$nestedList = new Listing($jatsListText->getStyle());
					$listItem->appendChild($nestedList);
					$nestedList->setContent($jatsListText);
				} elseif (get_class($jatsListText) === "JATSParser\Body\Par") {
					foreach ($jatsListText->getContent() as $jatsInsideText) {
						HTMLText::extractText($jatsInsideText, $listItem);
					}

					/* Paragraphs inside list are not supported by TCPDF
					 *
					$listPar = new Par();
					$listItem->appendChild($listPar);
					$listPar->setContent($jatsListText);
					*/
				}
			}
		}
	}

}
