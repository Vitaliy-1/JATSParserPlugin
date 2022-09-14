<?php namespace JATSParser\Body;

use JATSParser\Body\Table as Table;
use JATSParser\Body\Figure as Figure;
use JATSParser\Body\Listing as Listing;
use JATSParser\Body\Par as Par;
use JATSParser\Body\Section as Section;

class DispQuote extends Section {

	private $attrib = array();

	function __construct (\DOMElement $element) {
		parent::__construct($element);

		$this->attrib = $this->extractFormattedText(".//attrib", $element);
	}

	// Cannot contain sections
	public function getChildSectionsTitles(): array
	{
		return array();
	}

	/**
	 * @return array|null
	 */
	public function getAttrib(): array
	{
		return $this->attrib;
	}
}
