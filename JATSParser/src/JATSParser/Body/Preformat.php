<?php namespace JATSParser\Body;

class Preformat extends AbstractElement {

	private $content;
	private $contentType;

	public function __construct(\DOMElement $element) {
		parent::__construct($element);
		$this->content = $element->textContent;
		$this->contentType = $element->nodeName; // 'preformat' or 'code'
	}

	public function getContent(): string {
		return $this->content;
	}

	public function getContentType(): string {
		return $this->contentType;
	}
}
