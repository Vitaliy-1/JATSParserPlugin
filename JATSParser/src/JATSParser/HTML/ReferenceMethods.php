<?php namespace JATSParser\HTML;

use JATSParser\Back\AbstractReference;
use JATSParser\Back\Individual;
use JATSParser\Back\Collaboration;

class ReferenceMethods {

	public static function extractAuthors(AbstractReference $jatsReference, \DOMElement $domElement): void {
		foreach ($jatsReference->getAuthors() as $key => $author) {
			if (get_class($author) === "JATSParser\Back\Individual") {

				/* @var $author Individual */

				if ($author->getSurname()) {
					$htmlSurname = $domElement->ownerDocument->createTextNode($author->getSurname());
					$domElement->appendChild($htmlSurname);
				}

				if ($author->getGivenNames() && $key + 1 < count($jatsReference->getAuthors())) {
					$htmlGivenName = $domElement->ownerDocument->createTextNode(" " . $author->getGivenNames() . ", ");
					$domElement->appendChild($htmlGivenName);
				} elseif ($author->getGivenNames() && $key + 1 === count($jatsReference->getAuthors())) {
					$htmlGivenName = $domElement->ownerDocument->createTextNode(" " . $author->getGivenNames() . ".");
					$domElement->appendChild($htmlGivenName);
				}
			} elseif (get_class($author) === "JATSParser\Back\Collaboration") {

				/* @var $author Collaboration */
				if ($author->getName() && $key === 0 && $key + 1 === count($jatsReference->getAuthors())) {
					$htmlCollab = $domElement->ownerDocument->createTextNode(trim($author->getName()) . ".");
					$domElement->appendChild($htmlCollab);
				} elseif ($author->getName() && $key === 0 && $key + 1 < count($jatsReference->getAuthors())) {
					$htmlCollab = $domElement->ownerDocument->createTextNode(trim($author->getName()) . ", ");
					$domElement->appendChild($htmlCollab);
				} elseif ($author->getName() && $key + 1 < count($jatsReference->getAuthors())) {
					$htmlCollab = $domElement->ownerDocument->createTextNode(" " . trim($author->getName()) . ", ");
					$domElement->appendChild($htmlCollab);
				} elseif ($author->getName() && $key + 1 === count($jatsReference->getAuthors())) {
					$htmlCollab = $domElement->ownerDocument->createTextNode(" " . trim($author->getName()) . ".");
					$domElement->appendChild($htmlCollab);
				}
			}
		}
	}

	public static function extractLinks (AbstractReference $jatsReference, \DOMElement $domElement): void {
			if ($jatsReference->getUrl() !== "" && !empty($jatsReference->getPubIdType())) {
				$urlLink = $domElement->ownerDocument->createElement('a');
				$urlLink->setAttribute("href", $jatsReference->getUrl());
				$urlLink->nodeValue = "Publisher Full Text";
				$domElement->appendChild($urlLink);

				$delimeter = $domElement->ownerDocument->createTextNode(' | ');
				$domElement->appendChild($delimeter);
			} elseif ($jatsReference->getUrl() !== '') {
				$urlLink = $domElement->ownerDocument->createElement('a');
				$urlLink->setAttribute("href", $jatsReference->getUrl());
				$urlLink->nodeValue = "Publisher Full Text";
				$domElement->appendChild($urlLink);
			}

			if ($jatsReference->getPubIdType() !== ''){
				$number = 0;
				foreach ($jatsReference->getPubIdType() as $key => $pubId) {
					if ($key == 'doi') {
						$number += 1;
						$doiLink = $domElement->ownerDocument->createElement('a');
						$doiLink->setAttribute("href", $pubId);
						$doiLink->nodeValue = "DOI";
						$domElement->appendChild($doiLink);
					} elseif ($key == "pmid") {
						if ($number > 0) {
							$pmidDelimeter = $domElement->ownerDocument->createTextNode(' | ');
							$domElement->appendChild($pmidDelimeter);
						}
						$number += 1;
						$pmidLink = $domElement->ownerDocument->createElement('a');
						$pmidLink->setAttribute("href", $pubId);
						$pmidLink->nodeValue = "PubMed";
						$domElement->appendChild($pmidLink);
					} elseif ($key == "pmcid") {
						if ($number > 0) {
							$pmidDelimeter = $domElement->ownerDocument->createTextNode(' | ');
							$domElement->appendChild($pmidDelimeter);
						}
						$number += 1;
						$pmcidLink = $domElement->ownerDocument->createElement('a');
						$pmcidLink->setAttribute("href", $pubId);
						$pmcidLink->nodeValue = "PubMed Central";

					}
				}
			}
	}

	public static function extractEditors(AbstractReference $jatsReference, \DOMElement $domElement) {
		foreach ($jatsReference->getEditors() as $key => $author) {
			if (get_class($author) === "JATSParser\Back\Individual") {

				/* @var $author Individual */

				if ($author->getSurname()) {
					$htmlSurname = $domElement->ownerDocument->createTextNode($author->getSurname());
					$domElement->appendChild($htmlSurname);
				}

				if ($author->getGivenNames() && $key + 1 < count($jatsReference->getEditors())) {
					$htmlGivenName = $domElement->ownerDocument->createTextNode(" " . $author->getGivenNames() . ", ");
					$domElement->appendChild($htmlGivenName);
				} elseif ($author->getGivenNames() && $key + 1 === count($jatsReference->getEditors())) {
					$htmlGivenName = $domElement->ownerDocument->createTextNode(" " . $author->getGivenNames() . ".");
					$domElement->appendChild($htmlGivenName);
				}
			} elseif (get_class($author) === "JATSParser\Back\Collaboration") {

				/* @var $author Collaboration */
				if ($author->getName() && $key === 0 && $key + 1 === count($jatsReference->getEditors())) {
					$htmlCollab = $domElement->ownerDocument->createTextNode(trim($author->getName()) . ".");
					$domElement->appendChild($htmlCollab);
				} elseif ($author->getName() && $key === 0 && $key + 1 < count($jatsReference->getEditors())) {
					$htmlCollab = $domElement->ownerDocument->createTextNode(trim($author->getName()) . ", ");
					$domElement->appendChild($htmlCollab);
				} elseif ($author->getName() && $key + 1 < count($jatsReference->getEditors())) {
					$htmlCollab = $domElement->ownerDocument->createTextNode(" " . trim($author->getName()) . ", ");
					$domElement->appendChild($htmlCollab);
				} elseif ($author->getName() && $key + 1 === count($jatsReference->getEditors())) {
					$htmlCollab = $domElement->ownerDocument->createTextNode(" " . trim($author->getName()) . ".");
					$domElement->appendChild($htmlCollab);
				}
			}
		}
	}
}