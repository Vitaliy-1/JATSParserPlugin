<?php namespace JATSParser\Body;

use JATSParser\Body\Table as Table;
use JATSParser\Body\Figure as Figure;
use JATSParser\Body\Listing as Listing;
use JATSParser\Body\Par as Par;

class Section extends AbstractElement {

	/* section title */
	private $title;

	/* @var $type int type os a section: 1, 2, 3, 4 -> what means section, subsection, subsubsection, etc. */
	private $type;

	/* unique section id */
	private $id;

	/* @var $content array */
	private $content;

	private $hasSections;

	private $childSectionsTitles = array();

	function __construct(\DOMElement $section) {
		parent::__construct($section);

		$this->title = $this->extractFromElement("title", $section);
		$this->id = $this->extractFromElement("./@id", $section);

		$this->extractType($section);
		$this->ifHasSections($section);
		$this->extractContent($section);
	}

	public function getTitle() : ?string {
		return $this->title;
	}

	public function getContent() : array {
		return $this->content;
	}

	public function getType() : int {
		return $this->type;
	}

	public function hasSections() : bool {
		return $this->hasSections;
	}

	public function getChildSectionsTitles(): array
	{
		return $this->childSectionsTitles;
	}


	private function extractType(\DOMElement $section) {
		$parentElements = $this->xpath->query("parent::sec", $section);
		if (!is_null($parentElements)) {
			$this->type += 1;
			foreach ($parentElements as $parentElement) {
				$this->extractType($parentElement);
			}
		}
	}

	private function ifHasSections (\DOMElement $section) {
		$childSections = $this->xpath->query("sec", $section);
		if ($childSections->length > 0) {
			$this->hasSections = true;
		} else {
			$this->hasSections = false;
		}
		$sectionsTitles = $this->xpath->query("sec/title", $section);
		foreach ($sectionsTitles as $sectionsTitle) {
			$this->childSectionsTitles[] = $sectionsTitle->textContent;
		}
	}

	private function extractContent (\DOMElement $section) {
		$content = array();
		$sectionNodes = $this->xpath->evaluate("./node()", $section);
		foreach ($sectionNodes as $key => $sectionElement) {
			switch ($sectionElement->nodeName) {
				case "p":
					$par = new Par($sectionElement);
					$content[] = $par;
					if (!empty($par->getBlockElements())) {
						foreach ($par->getBlockElements() as $blockElement) {
							$content[] = $blockElement;
						}
					}
					break;
				case "list":
					$list = new Listing($sectionElement);
					$content[] = $list;
					break;
				case "table-wrap":
					$table = new Table($sectionElement);
					$content[] = $table;
					break;
				case "fig":
					$figure = new Figure($sectionElement);
					$content[] = $figure;
					break;
				case "media":
					$media = new Media($sectionElement);
					$content[] = $media;
					break;
				case "disp-quote":
					$dispQuote = new DispQuote($sectionElement);
					$content[] = $dispQuote;
					break;
				case "#text":
					if (trim($sectionElement->nodeValue) != "") {
						$text = new Text($sectionElement);
						$content[] = $text;
					}
					break;
				case "verse-group":
					$versa = new Verse($sectionElement);
					$content[] = $versa;
					break;
			}
		}
		$this->content = $content;
	}

}
