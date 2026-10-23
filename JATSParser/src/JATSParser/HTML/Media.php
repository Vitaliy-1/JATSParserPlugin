<?php namespace JATSParser\HTML;

use JATSParser\Body\Media as JATSMedia;
use JATSParser\HTML\Par as Par;
use JATSParser\HTML\Text as HTMLText;


class Media extends \DOMElement {
	
	public function __construct() {
		parent::__construct("div");
	}
	
	public function setContent(JATSMedia $jatsMedia) {
		
		$this->setAttribute("class", "media-wrapper");
		
		$imageIframe = $this->ownerDocument->createElement("iframe");
		$imageIframe->setAttribute("src", $jatsMedia->getLink());
		$this->appendChild($imageIframe);
		
		
		$captionNode = $this->ownerDocument->createElement("div");
		$captionNode->setAttribute("class", "caption");
		$this->appendChild($captionNode);
		
		// Set media label (e.g., Figure 1)
		
		if ($jatsMedia->getLabel()) {
			$spanLabel = $this->ownerDocument->createElement("span");
			$spanLabel->setAttribute("class", "label");
			$captionNode->appendChild($spanLabel);
			$textNode = $this->ownerDocument->createTextNode(HTMLText::checkPunctuation($jatsMedia->getLabel()));
			$spanLabel->appendChild($textNode);
		}
		
		/* Set media title
        * @var $mediaTitle JATSText
        */
		
		if ($jatsMedia->getTitle() && count($jatsMedia->getTitle()) > 0) {
			$spanTitle = $this->ownerDocument->createElement("span");
			$spanTitle->setAttribute("class", "title");
			$captionNode->appendChild($spanTitle);
			foreach ($jatsMedia->getTitle() as $mediaTitle) {
				HTMLText::extractText($mediaTitle, $spanTitle);
			}
		}
		
		/* Set media caption
		 * @var $mediaCaption JATSText
		 */
		
		if ($jatsMedia->getContent() && count($jatsMedia->getContent()) > 0) {
			foreach ($jatsMedia->getContent() as $jatsContent) {
				$par = new Par("span");
				$captionNode->appendChild($par);
				$par->setAttribute("class", "notes");
				$par->setContent($jatsContent);
			}
		}
	}
}