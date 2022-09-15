<?php namespace JATSParser\HTML;

use JATSParser\Body\DispQuote;
use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Par as  Par;
use JATSParser\HTML\Listing as Listing;
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

define('JATSPARSER_CITEPROC_STYLE_DEFAULT', 'vancouver');
define('JATSPARSER_CITEPROC_LANG_DEFAULT', 'en-US');
define('JATSPARSER_REFERENCE_ELEMENT_ID', 'referenceList'); // Document::getRawReferences() linked to this id

class Document extends \DOMDocument {

	/** @var $citationStyle string  */
	var $citationStyle;

	var $citeProcReferences;
	var $styleInTextLinks;
	var $citationLang;
	var $jatsDocument;

	public function __construct(JATSDocument $jatsDocument) {
		parent::__construct('1.0', 'utf-8');
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		$this->jatsDocument = $jatsDocument;

		$articleSections = $this->jatsDocument->getArticleSections();
		$this->extractContent($articleSections);
	}

	/**
	 * @param string $citationStyle see: https://github.com/citation-style-language/styles
	 * @param string $lang language for citation styling
	 * @param bool $styleInTextLinks whether to style in-text links to references
	 */
	public function setReferences(string $citationStyle = JATSPARSER_CITEPROC_STYLE_DEFAULT, string $lang = JATSPARSER_CITEPROC_LANG_DEFAULT, bool $styleInTextLinks = false): void {
		$this->citationStyle = $citationStyle;
		$this->citationLang = $lang;
		$this->styleInTextLinks = $styleInTextLinks;
		if (!empty($this->jatsDocument->getReferences())) {
			$this->extractReferences($this->jatsDocument->getReferences());
		}
	}

	public function getHmtlForGalley() {
		return $this->saveHTML();
	}

	public function getHtmlForTCPDF() {

		// set text-wide styles;
		$xpath = new \DOMXPath($this);
		$referenceLinks = $xpath->evaluate("//a[@class=\"bibr\"]");
		foreach ($referenceLinks as $referenceLink) {
			$referenceLink->setAttribute("style", "background-color:#e6f2ff; color:#1B6685; text-decoration:none;");
		}

		$tableAndFigureLinks = $xpath->evaluate("//a[@class=\"table\"]|//a[@class=\"fig\"]");
		foreach ($tableAndFigureLinks as $tableAndFigureLink) {
			$tableAndFigureLink->setAttribute("style", "background-color:#c6ecc6; color:#495A11; text-decoration:none;");
		}

		$headerOnes = $xpath->evaluate("//h2");
		foreach ($headerOnes as $headerOne) {
			$headerOne->setAttribute("style", "color: #343a40; font-size:20px;");
		}

		$headerTwos = $xpath->evaluate("//h3");
		foreach ($headerTwos as $headerTwo) {
			$headerTwo->setAttribute("style", "color: #343a40; font-size: 16px;");
		}

		// set style for figures and table
		$tableNodes = $xpath->evaluate("//table");
		foreach ($tableNodes as $tableNode) {
			$tableNode->setAttribute("style", "font-size:10px;");
			$tableNode->setAttribute("border", "1");
			$tableNode->setAttribute("cellpadding", "2");
		}

		$captionNodes = $xpath->evaluate("//figure/p[@class=\"caption\"]|//table/caption");

		foreach ($captionNodes as $captionNode) {
			$captionNode->setAttribute("style", "font-size:10px;display:block;");
			$forBoldNodes = $xpath->evaluate("span[@class=\"label\"]", $captionNode);
			foreach ($forBoldNodes as $forBoldNode) {
				$forBoldNode->setAttribute("style", "font-weight:bold;font-size:10px;");
				$emptyTextNode = $this->createTextNode(" ");
				$forBoldNode->appendChild($emptyTextNode);
			}
			$forItalicNodes = $xpath->evaluate("span[@class=\"title\"]", $captionNode);
			foreach ($forItalicNodes as $forItalicNode) {
				$forItalicNode->setAttribute("style", "font-style:italic;font-size:10px;");
				$emptyTextNode = $this->createTextNode(" ");
				$forItalicNode->appendChild($emptyTextNode);
			}
			$forNotesNodes = $xpath->evaluate("span[@class=\"notes\"]", $captionNode);
			foreach ($forNotesNodes as $forNotesNode) {
				$forNotesNode->setAttribute("style", "font-size:10px;");
			}
		}

		$tableCaptions = $xpath->evaluate("//table/caption");
		foreach ($tableCaptions as $tableCaption) {
			/* @var $tableNode \DOMNode */
			$tableNode = $tableCaption->parentNode;
			$divNode = $this->createElement("div");
			$nextToTableNode = $tableNode->nextSibling;
			if ($nextToTableNode) {
				$tableNode->parentNode->insertBefore($divNode, $nextToTableNode);
			}
			$divNode->appendChild($tableCaption);

		}

		// final preparations
		$htmlString = $this->saveHTML();
		/* For HTML editing in UTF-8 should be used: $htmlString = $this->saveHTML($this); */

		$htmlString = preg_replace("/<li>\s*/", "<li>", $htmlString);

		return $htmlString;
	}

	/**
	 * @param $articleSections array;
	 */
	protected function extractContent(array $articleSections, \DOMElement $element = null): void {

		if ($element) {
			$parentEl = $element;
		} else {
			$parentEl = $this;
		}

		foreach ($articleSections as $articleSection) {

			switch (get_class($articleSection)) {
				case "JATSParser\Body\Par":
					$par = new Par();
					$parentEl->appendChild($par);
					$par->setContent($articleSection);
					break;
				case "JATSParser\Body\Listing":
					$listing = new Listing($articleSection->getStyle());
					$parentEl->appendChild($listing);
					$listing->setContent($articleSection);
					break;
				case "JATSParser\Body\Table":
					$table = new Table();
					$parentEl->appendChild($table);
					$table->setContent($articleSection);
					break;
				case "JATSParser\Body\Figure":
					$figure = new Figure();
					$parentEl->appendChild($figure);
					$figure->setContent($articleSection);
					break;
				case "JATSParser\Body\Media":
					$media = new Media();
					$parentEl->appendChild($media);
					$media->setContent($articleSection);
					break;
				case "JATSParser\Body\Section":
					if ($articleSection->getTitle()) {
						$sectionElement = $this->createElement("h" . ($articleSection->getType() + 1), $articleSection->getTitle());
						$sectionElement->setAttribute("class", "article-section-title");
						$parentEl->appendChild($sectionElement);
					}
					$this->extractContent($articleSection->getContent());
					break;
				case "JATSParser\Body\DispQuote":
					$blockQuote = $this->createElement("blockquote");
					if ($articleSection->getTitle()) {
						$sectionElement = $this->createElement("h" . ($articleSection->getType() + 1), $articleSection->getTitle());
						$sectionElement->setAttribute("class", "article-dispquote-title");
						$blockQuote->appendChild($sectionElement);
					}
					$parentEl->appendChild($blockQuote);
					$this->extractContent($articleSection->getContent(), $blockQuote);
					if (!empty($quoteAttribTexts = $articleSection->getAttrib())) {
						$quoteCite = $this->createElement("cite");
						$blockQuote->appendChild($quoteCite);
						foreach ($quoteAttribTexts as $quoteAttribText) {
							Text::extractText($quoteAttribText, $quoteCite);
						}
					}
					break;
				case "JATSParser\Body\Verse":
					$verseGroup = new Verse();
					$parentEl->appendChild($verseGroup);
					$verseGroup->setContent($articleSection);
					break;
				case "JATSParser\Body\Text":
					// For elements that extend Section, like disp-quote
					Text::extractText($articleSection, $parentEl);
					break;
			}
		}
	}

	protected function extractReferences (array $references): void {

		$referencesHeading = $this->createElement("h2");
		$referencesHeading->setAttribute("class", "article-section-title");
		$referencesHeading->setAttribute("id", "reference-title");
		$referencesHeading->nodeValue = "References";
		$this->appendChild($referencesHeading);

		$data = [];
		$rawData = [];
		foreach ($references as $reference) {
			$citeProcRef = new Reference($reference);
			if (!$citeProcRef->refIsEmpty()) {
				$data[] = $citeProcRef->getContent();
			} elseif ($citeProcRef->getJatsReference()->isMixed() && !empty(trim($citeProcRef->getJatsReference()->getRawReference()))) {
				$rawData[] =$citeProcRef->getJatsReference();
			} else {
				error_log("WARNING: reference with id " . $reference->getId() . " is invalid and cannot be parsed");
			}
		}

		$this->citeProcReferences = $data;

		$style = StyleSheet::loadStyleSheet($this->getCitationStyle());

		$wrapIntoListItem = function($cslItem, $renderedText) {
			return '<li id="' . $cslItem->id .'">' . $renderedText . '</li>';
		};

		$additionalMarkup = [
			'bibliography' => [
				'csl-entry' => $wrapIntoListItem
			]
		];

		$citeProc = new CiteProc($style, $this->citationLang, $additionalMarkup);
		$htmlString = $citeProc->render($data, 'bibliography');

		if ($this->styleInTextLinks) {
			$this->setInTextLinks($citeProc, $data);
		}

		$this->getCiteBody($htmlString, $rawData);
	}

	protected function getCiteBody(string $htmlString, array $rawData) {
		$document = new \DOMDocument('1.0', 'utf-8');
		$document->loadXML($htmlString);

		$listEl = $this->createElement('ol');
		$listEl->setAttribute('class', 'references');
		$listEl->setAttribute('id', JATSPARSER_REFERENCE_ELEMENT_ID);
		$this->appendChild($listEl);

		$xpath = new \DOMXPath($document);
		$listItemEls = $xpath->query('//li');
		foreach ($listItemEls as $listItemEl) {
			$newListItemEl = $this->createElement('li');
			$newListItemEl->setAttribute('id', $listItemEl->getAttribute('id'));
			$listEl->appendChild($newListItemEl);

			$nodeList = $xpath->query('div[@class="csl-right-inline"]/node()', $listItemEl);
			if ($nodeList->count() > 0) {
				foreach ($nodeList as $node) {
					$newNode = $this->importNode($node, true);
					$newListItemEl->appendChild($newNode);
				}
			} else {
				$nodeList = $xpath->query('node()', $listItemEl); {
					foreach ($nodeList as $node) {
						$newNode = $this->importNode($node, true);
						$newListItemEl->appendChild($newNode);
					}
				}
			}
		}
		// Append data from mixed citation nodes that don't contain valid ref data for CSL
		foreach ($rawData as $rawRefObject) {
			$newListItemEl = $this->createElement('li');
			$newListItemEl->setAttribute('id', $rawRefObject->getId());
			$textRefNode = $this->createTextNode(trim($rawRefObject->getRawReference()));
			$newListItemEl->appendChild($textRefNode);
			$listEl->appendChild($newListItemEl);
		}
	}

	protected function setInTextLinks($citeProc, $data) {

		$xpath = new \DOMXPath($this);
		$links = $xpath->query('//a[@class="bibr"]');
		foreach ($links as $link) {
			$linkId = $link->getAttribute('href');
			if ($linkId) {
				$citeObject = new \stdClass();
				$citeObject->id = str_replace("#", "", $linkId);
				$link->nodeValue = $citeProc->render($data, "citation", [$citeObject]);
			}
		}
	}

	public function getCitationStyle(): string {
		return $this->citationStyle;
	}

	public function saveAsValidHTML(string $documentTitle, bool $prettyPrint = false): string {
		if ($prettyPrint) {
			$xpath = new \DOMXPath($this);
			$nodes = $xpath->query('//text()');
			foreach ($nodes as $node) {
				$node->nodeValue = preg_replace("/[\\s]{2,}/", " ", $node->nodeValue);
			}
		}

		$htmlString = $this->saveAsHTML();

		$htmlString =
			'<!doctype html>' . "\n" .
			'<html lang="">' . "\n" .
			'<head>' . "\n" .
			"\t" . '<meta charset="UTF-8">' . "\n" .
			"\t" . '<title>' . htmlspecialchars($documentTitle) . '</title>' . "\n" .
			'</head>' . "\n" .
			'<body>' . "\n" .
			$htmlString .
			'</body>'. "\n" .
			'</html>';

		return $htmlString;

	}

	public function saveAsHTML($element = null) {

		$htmlString = $element ? $this->saveXML($element) : $this->saveXML($this);

		$xmlDeclaration = '<?xml version="1.0" encoding="UTF-8"?>';
		$pos = strpos($htmlString, $xmlDeclaration);
		if ($pos !== false) {
			$htmlString = substr_replace($htmlString, '', $pos, strlen($xmlDeclaration));
		}

		return $htmlString;
	}

	/**
	 * @param string $filename path to the file to write a file
	 * @param string $documentTitle document title that is required for HTML to be valid
	 * @param bool $prettyPrint
	 * @return void
	 */
	public function saveAsValidHTMLFile(string $filename, string $documentTitle, bool $prettyPrint = true): void {
		file_put_contents($filename, $this->saveAsValidHTML($documentTitle, $prettyPrint));
	}

	/**
	 * @return array of references, where key is unique id, ordered according to appearance in JATS XML
	 */
	public function getRawReferences(): array {
		$references = [];

		$refListEl = null;

		// DOMDocument::getElementById or xpath analog won't work presumably because the absence of a root element
		foreach ($this->getElementsByTagName('ol') as $ol) {
			if ($ol->getAttribute('id') == JATSPARSER_REFERENCE_ELEMENT_ID) {
				$refListEl = $ol;
			}
		}

		if (!$refListEl) return $references;

		foreach ($refListEl->childNodes as $refItemEl) {
			$htmlString = '';
			foreach ($refItemEl->childNodes as $refContent) {
				$htmlString .= $this->saveAsHTML($refContent);
			}

			if ($refItemEl->hasAttribute('id')) {
				$references[$refItemEl->getAttribute('id')] = $htmlString;
			} else {
				$references[] = $htmlString;
			}
		}

		return $references;
	}
}
