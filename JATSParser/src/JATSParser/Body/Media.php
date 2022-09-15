<?php namespace JATSParser\Body;

use JATSParser\Body\Document as Document;

class Media extends AbstractElement {

	/* @var $label string */
	private $label;

	/* @var $link string */
	private $link;

	/* @var $id string */
	private $id;

	/* @var $content array; video caption */
	private $content;

	/* @var $title array */
	private $title;

	public function __construct(\DOMElement $mediaElement) {
		parent::__construct($mediaElement);

		$this->label = $this->extractFromElement(".//label", $mediaElement);
		$this->link = $this->extractFromElement("./@xlink:href", $mediaElement);
		$this->id = $this->extractFromElement("./@id", $mediaElement);
		$this->title = $this->extractTitleOrCaption($mediaElement, self::JATS_EXTRACT_TITLE);
		$this->content = $this->extractTitleOrCaption($mediaElement, self::JATS_EXTRACT_CAPTION);

	}

	/**
	 * @return array
	 */
	public function getContent(): array {
		return $this->content;
	}

	/**
	 * @return string
	 */
	public function getLabel(): ?string
	{
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getLink(): ?string
	{
		return $this->link;
	}

	/**
	 * @return string
	 */
	public function getId(): ?string
	{
		return $this->id;
	}

	/**
	 * @return array
	 */
	public function getTitle(): array
	{
		return $this->title;
	}

}
