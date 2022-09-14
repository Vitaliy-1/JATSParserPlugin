<?php namespace JATSParser\Body;

use JATSParser\Body\Document as Document;

class Figure extends AbstractElement {

	/* @var $label string */
	private $label;

	/* @var $link string */
	private $link;

	/* @var $id string */
	private $id;

	/* @var $content array; figure caption */
	private $content;

	/* @var $title array */
	private $title;

	public function __construct(\DOMElement $figureElement) {
		parent::__construct($figureElement);

		$this->label = $this->extractFromElement(".//label", $figureElement);
		$this->link = $this->extractFromElement(".//graphic/@xlink:href", $figureElement);
		$this->id = $this->extractFromElement( "./@id", $figureElement);
		$this->title = $this->extractTitleOrCaption($figureElement, self::JATS_EXTRACT_TITLE);
		$this->content = $this->extractTitleOrCaption($figureElement, self::JATS_EXTRACT_CAPTION);

	}

	public function getContent(): ?array {
		return $this->content;
	}

	public function getLink(): ?string {
		return $this->link;
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function getTitle(): ?array {
		return $this->title;
	}

	public function getLabel(): ?string {
		return $this->label;
	}
}
