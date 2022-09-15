<?php namespace JATSParser\Back;

use docx2jats\jats\Element;
use JATSParser\Back\Reference as Reference;
use JATSParser\Back\Collaboration as Collaboration;
use JATSParser\Body\Document as Document;

Define('DOI_REFERENCE_PREFIX', 'https://doi.org/');
Define('PMID_REFERENCE_PREFIX', 'https://www.ncbi.nlm.nih.gov/pubmed/');
Define('PMCID_REFERENCE_PREFIX', 'https://www.ncbi.nlm.nih.gov/pmc/articles/');
abstract class AbstractReference implements Reference
{

	protected $xpath;

	/* @var $id string */
	protected $id;

	/* @var array can contain instances of Individual and Collaboration class */
	protected $authors;

	/* @var array can contain instances of Individual and Collaboration class */
	protected $editors;

	/* @var $year string */
	protected $year;

	/* @var $url string */
	protected $url;

	/* @var $pubIdType array publication Identifier for a cited publication */
	protected $pubIdType;

	protected $rawReference = '';

	protected $isMixed = false;

	abstract public function getId();

	abstract public function getTitle();

	abstract public function getAuthors();

	abstract public function getEditors();

	abstract public function getYear();

	abstract public function getUrl();

	abstract public function getPubIdType();

	protected function __construct(\DOMElement $reference)
	{
		$this->xpath = Document::getXpath();
		$this->authors = $this->extractAuthors($reference);
		$this->editors = $this->extractEditors($reference);
		$this->id = $this->extractId($reference);
		$this->year = $this->extractFromElement($reference, './/year[1]');
		$this->url = $this->extractFromElement($reference, './/ext-link[@ext-link-type="uri"]');
		$this->pubIdType = $this->extractPubIdType($reference);

		$citNode = $this->getFirstChildElement($reference);
		if ($citNode) {
			if ($citNode->tagName === 'mixed-citation') $this->isMixed = true;
			$this->rawReference = $citNode->nodeValue;
		}
	}

	protected function extractFromElement(\DOMElement $reference, string $xpathExpression)
	{
		$property = '';
		$searchNodes = $this->xpath->query($xpathExpression, $reference);
		if ($searchNodes->length > 0) {
			foreach ($searchNodes as $searchNode) {
				$property = htmlspecialchars(trim($searchNode->nodeValue));
			}
		}
		return $property;
	}

	private function extractId(\DOMElement $reference)
	{
		$id = '';
		if ($reference->hasAttribute("id")) {
			$id = $reference->getAttribute("id");
		}
		return $id;
	}

	private function extractAuthors(\DOMElement $reference)
	{
		$authors = array();

		$nameNodes = $this->xpath->query(".//name|.//collab", $reference);
		if ($nameNodes->length > 0) {
			/* @var $nameNode \DOMElement */
			foreach ($nameNodes as $nameNode) {
				$parentOfName = $nameNode->parentNode;
				if ($nameNode->nodeName === 'name' && ($parentOfName->nodeName !== 'person-group' || $parentOfName->getAttribute('person-group-type') === 'author')) {
					$individual = new Individual($nameNode);
					$authors[] = $individual;
				} elseif ($nameNode->nodeName === 'collab' && ($parentOfName->nodeName !== 'person-group' || $parentOfName->getAttribute('person-group-type') === 'author')) {
					$collaborator = new Collaboration($nameNode);
					$authors[] = $collaborator;
				}
			}
		}
		return $authors;
	}

	private function extractEditors(\DOMElement $reference) {
		$editors = array();

		$nameNodes = $this->xpath->query(".//name|.//collab", $reference);
		if ($nameNodes->length > 0) {
			/* @var $nameNode \DOMElement */
			foreach ($nameNodes as $nameNode) {
				$parentOfName = $nameNode->parentNode;
				if ($nameNode->nodeName === 'name' && $parentOfName->getAttribute('person-group-type') === 'editor') {
					$individual = new Individual($nameNode);
					$editors[] = $individual;
				} elseif ($nameNode->nodeName === 'collab' && $parentOfName->getAttribute('person-group-type') === 'editor') {
					$collaborator = new Collaboration($nameNode);
					$editors[] = $collaborator;
				}
			}
		}
		return $editors;
	}

	/**
	 * @return array
	 * Key => Publication ID Typy (DOI, PMID, PMCID), Value => Valid URL
	 */

	private function extractPubIdType(\DOMElement $reference): array
	{
		$pubIdType = array();

		$pubIdNodes = $this->xpath->query('.//pub-id', $reference);
		if ($pubIdNodes->length > 0) {
			/* @var $pubIdNode \DOMElement */
			foreach ($pubIdNodes as $pubIdNode) {
				if ($pubIdNode->getAttribute('pub-id-type')) {
					/* Ideally, we should retrieve Pub ID Type as a key  and URL here as an array value */
					$pubIdKey = $pubIdNode->getAttribute('pub-id-type');
					$pubIdValue = $pubIdNode->nodeValue;

					switch (trim($pubIdKey)) {
						/* TODO It's quite probably that we will need additional checks here */
						case "doi":
							filter_var($pubIdValue, FILTER_VALIDATE_URL) ? $pubIdType[$pubIdKey] = $pubIdValue : $pubIdType[$pubIdKey] = DOI_REFERENCE_PREFIX . trim($pubIdValue);
							break;
						case "pmid":
							filter_var($pubIdValue, FILTER_VALIDATE_URL) ? $pubIdType[$pubIdKey] = $pubIdValue : $pubIdType[$pubIdKey] = PMID_REFERENCE_PREFIX . trim($pubIdValue);
							break;
						case "pmcid":
							filter_var($pubIdValue, FILTER_VALIDATE_URL) ? $pubIdType[$pubIdKey] = $pubIdValue : $pubIdType[$pubIdKey] = PMCID_REFERENCE_PREFIX . trim($pubIdValue);
							break;
					}
				}
			}
		}
		return $pubIdType;
	}

	/**
	 * @return bool
	 * @brief check if it's mixed citation (may have untagged text)
	 */
	public function isMixed(): bool {
		return $this->isMixed;
	}

	/**
	 * @return string
	 * @brief contains only the text/nodeValue of the reference node
	 */
	public function getRawReference(): string {
		return $this->rawReference;
	}

	/**
	 * @param \DOMElement $el
	 * @return \DOMElement|null
	 * @brief return the first child element that is a DOMElement, e.g., to avoid DOMText children
	 */
	protected function getFirstChildElement(\DOMElement $el): ?\DOMElement {
		foreach ($el->childNodes as $refChild) {
			if ($refChild->nodeType === XML_ELEMENT_NODE) {
				return $refChild;
			}
		}
		return null;
	}
}
