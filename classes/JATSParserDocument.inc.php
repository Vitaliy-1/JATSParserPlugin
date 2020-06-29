<?php

require_once __DIR__ . '/../JATSParser/vendor/autoload.php';

use JATSParser\HTML\Document as Document;
use JATSParser\Body\Document as JATSDocument;

class JATSParserDocument extends Document {

	var $jatsDocument;
	private $parseReferences;

	public function __construct(JATSDocument $jatsDocument, $parseReferences = true) {

		$this->parseReferences = $parseReferences;

		parent::__construct($jatsDocument, $parseReferences);

		$this->jatsDocument = $jatsDocument;

	}

	public function useOjsReferences() {
		if(!$this->jatsDocument->getReferences()) return true;
		return false;
	}
}
