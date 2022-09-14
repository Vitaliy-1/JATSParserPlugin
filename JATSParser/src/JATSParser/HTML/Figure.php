<?php namespace JATSParser\HTML;

use JATSParser\Body\Figure as JATSFigure;
use JATSParser\Body\Par as JATSPar;
use JATSParser\Body\Text as JATSText;
use JATSParser\HTML\Par as Par;
use JATSParser\HTML\Text as HTMLText;

class Figure extends \DOMElement {
	public function __construct() {

		parent::__construct("figure");

	}

	public function setContent(JATSFigure $jatsFigure) {
		
		// Add image wrapped inside div (to avoid issues with overlapping by caption)
		$divNode = $this->ownerDocument->createElement("div");
		$divNode->setAttribute("class", "figure");
		$this->appendChild($divNode);
		
		$srcNode = $this->ownerDocument->createElement("img");
		$divNode->appendChild($srcNode);
		$srcNode->setAttribute("src", $jatsFigure->getLink());
		
		
		$titleNode = $this->ownerDocument->createElement("p");
		$titleNode->setAttribute("class", "caption");
		$this->appendChild($titleNode);

        // Set figure id. Needed for links from referenceces to the figure
        $this->setAttribute("id", $jatsFigure->getId());

		// Set figure label (e.g., Figure 1)
		if ($jatsFigure->getLabel()) {
			$spanLabel = $this->ownerDocument->createElement("span");
			$spanLabel->setAttribute("class", "label");
			$titleNode->appendChild($spanLabel);
			$textNode = $this->ownerDocument->createTextNode(HTMLText::checkPunctuation($jatsFigure->getLabel()));
			$spanLabel->appendChild($textNode);
		}
		
		/* Set figure title
        * @var $figureTitle JATSText
        */
		if (count($jatsFigure->getTitle()) > 0) {
			$spanTitle = $this->ownerDocument->createElement("span");
			$spanTitle->setAttribute("class", "title");
			$titleNode->appendChild($spanTitle);
			foreach ($jatsFigure->getTitle() as $figureTitle) {
				HTMLText::extractText($figureTitle, $spanTitle);
			}
		}
		
		/* Set figure notes
        * @var $figureContent JATSPar
        */
		if (count($jatsFigure->getContent()) > 0) {
			foreach ($jatsFigure->getContent() as $figureContent) {
				$par = new Par("span");
				$titleNode->appendChild($par);
				$par->setAttribute("class", "notes");
				$par->setContent($figureContent);
			}
		}
	}
	
}