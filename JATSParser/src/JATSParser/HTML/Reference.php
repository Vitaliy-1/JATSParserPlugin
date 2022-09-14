<?php namespace JATSParser\HTML;


use JATSParser\Back\AbstractReference;
use JATSParser\Back\Journal;
use JATSParser\Back\Book;
use JATSParser\Back\Chapter;
use JATSParser\Back\Conference;


class Reference extends \DOMElement {

	public function __construct() {

		parent::__construct("li");

	}

	public function setContent(AbstractReference $jatsReference) {

		$this->setAttribute("id", $jatsReference->getId());

		switch (get_class($jatsReference)) {
			case "JATSParser\Back\Journal":

				/* @var $jatsReference Journal */

				// extracting reference authors

				ReferenceMethods::extractAuthors($jatsReference, $this);

				// exctracting reference body

				if ($jatsReference->getTitle()) {
					$journalTitle = $this->ownerDocument->createTextNode(" " . htmlspecialchars(trim($jatsReference->getTitle())). ".");
					$this->appendChild($journalTitle);
				}

				if ($jatsReference->getJournal()) {
					$journalName = $this->ownerDocument->createElement("i", htmlspecialchars( " " . trim($jatsReference->getJournal())) . ".");
					$this->appendChild($journalName);
				}

				if ($jatsReference->getYear() != '' && ($jatsReference->getVolume() == '' && $jatsReference->getIssue() == '' && $jatsReference->getFpage() == '' && $jatsReference->getLpage() == '')) {
					$journalYear = $this->ownerDocument->createTextNode(" " . $jatsReference->getYear() . ". ");
					$this->appendChild($journalYear);
				} elseif ($jatsReference->getYear() != '') {
					$journalYear = $this->ownerDocument->createTextNode(" " . $jatsReference->getYear() . ";");
					$this->appendChild($journalYear);
				}

				if ($jatsReference->getVolume() != '' && $jatsReference != '') {
					$journalVolume = $this->ownerDocument->createTextNode(" " . $jatsReference->getVolume());
					$this->appendChild($journalVolume);
				}

				if ($jatsReference->getIssue() != '' && $jatsReference->getVolume() != '') {
					$journalIssue = $this->ownerDocument->createTextNode("(" . $jatsReference->getIssue() . ")");
					$this->appendChild($journalIssue);
				} elseif ($jatsReference->getIssue() != '' && $jatsReference->getVolume() == '') {
					$journalIssue = $this->ownerDocument->createTextNode(" " . $jatsReference->getIssue());
					$this->appendChild($journalIssue);
				}

				if (($jatsReference->getFpage() != '' || $jatsReference->getLpage() != '') && ($jatsReference->getVolume() != '' || $jatsReference->getIssue() != '')) {
					$betweenFAndLPage = $this->ownerDocument->createTextNode(":");
					$this->appendChild($betweenFAndLPage);
				}

				if ($jatsReference->getFpage() != '' && $jatsReference->getLpage() == '') {
					$fpage = $this->ownerDocument->createTextNode($jatsReference->getFpage() . '. ');
					$this->appendChild($fpage);
				} elseif ($jatsReference->getFpage() != '') {
					$fpage = $this->ownerDocument->createTextNode($jatsReference->getFpage());
					$this->appendChild($fpage);
				}

				if ($jatsReference->getLpage() != '' && $jatsReference->getFpage() != '') {
					$lpage = $this->ownerDocument->createTextNode("-" . $jatsReference->getLpage() . '. ');
					$this->appendChild($lpage);
				} elseif ($jatsReference->getLpage() != '' && $jatsReference->getFpage() == '') {
					$lpage = $this->ownerDocument->createTextNode($jatsReference->getLpage() . '. ');
					$this->appendChild($lpage);
				}

				ReferenceMethods::extractLinks($jatsReference, $this);
				break;

			case "JATSParser\Back\Book":

				/* @var $jatsReference Book */
				ReferenceMethods::extractAuthors($jatsReference, $this);

				if ($jatsReference->getTitle() != '') {
					$bookTitle = $this->ownerDocument->createTextNode(" " . htmlspecialchars(trim($jatsReference->getTitle())). ".");
					$this->appendChild($bookTitle);
				}

				if ($jatsReference->getPublisherName() != '' && $jatsReference->getPublisherLoc() != '') {
					$pubName= $this->ownerDocument->createTextNode(" " . htmlspecialchars(trim($jatsReference->getPublisherName()). ":"));
					$this->appendChild($pubName);
				} elseif ($jatsReference->getPublisherName() != '' && $jatsReference->getPublisherLoc() == '' && $jatsReference->getYear() != '') {
					$pubName= $this->ownerDocument->createTextNode(" " . htmlspecialchars(trim($jatsReference->getPublisherName()). ";"));
					$this->appendChild($pubName);
				} elseif ($jatsReference->getPublisherName() != '' && $jatsReference->getPublisherLoc() == '' && $jatsReference->getYear() == '') {
					$pubName= $this->ownerDocument->createTextNode(" " . htmlspecialchars(trim($jatsReference->getPublisherName()). ". "));
					$this->appendChild($pubName);
				}

				if ($jatsReference->getPublisherLoc() != '' && $jatsReference->getYear() != '') {
					$pubLoc = $this->ownerDocument->createTextNode( " " . htmlspecialchars(trim($jatsReference->getPublisherLoc() . ";")));
					$this->appendChild($pubLoc);
				} elseif ($jatsReference->getPublisherLoc() != '' && $jatsReference->getYear() == '') {
					$pubLoc = $this->ownerDocument->createTextNode( " " . htmlspecialchars(trim($jatsReference->getPublisherLoc() . ". ")));
					$this->appendChild($pubLoc);
				}

				if ($jatsReference->getYear() != '') {
					$year = $this->ownerDocument->createTextNode(' ' . htmlspecialchars(trim($jatsReference->getYear())) . '. ');
					$this->appendChild($year);
				}

				ReferenceMethods::extractLinks($jatsReference, $this);
				break;

			case "JATSParser\Back\Chapter":

				/* @var $jatsReference Chapter */
				// extracting reference authors

				ReferenceMethods::extractAuthors($jatsReference, $this);

				if ($jatsReference->getTitle() != '') {
					$chapterTitle = $this->ownerDocument->createTextNode(" " . htmlspecialchars(trim($jatsReference->getTitle())) . ". ");
					$this->appendChild($chapterTitle);
				}

				if (!empty($jatsReference->getEditors())) {
					$editorsBlock = $this->ownerDocument->createTextNode("In: ");
					$this->appendChild($editorsBlock);
					ReferenceMethods::extractEditors($jatsReference, $this);
				}

				if ($jatsReference->getBook() != '') {
					$chBookTitle = $this->ownerDocument->createElement("i");
					$chBookTitle->nodeValue = " " . htmlspecialchars(trim($jatsReference->getBook())) . ". ";
					$this->appendChild($chBookTitle);
				}

				if ($jatsReference->getPublisherName() != '' && $jatsReference->getPublisherLoc() != '') {
					$chapterPubName = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getPublisherName())) . ": ");
					$this->appendChild($chapterPubName);

				} elseif ($jatsReference->getPublisherName() != '' && $jatsReference->getPublisherLoc() == '' && $jatsReference->getYear() != '') {
					$chapterPubName = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getPublisherName())) . "; ");
					$this->appendChild($chapterPubName);
				} elseif ($jatsReference->getPublisherName() != '' && $jatsReference->getPublisherLoc() == '' && $jatsReference->getYear() == '') {
					$chapterPubName = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getPublisherName())) . ". ");
					$this->appendChild($chapterPubName);
				}

				if ($jatsReference->getPublisherLoc() != '' && $jatsReference->getYear() != '') {
					$chapterPubLoc = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getPublisherName())) . "; ");
					$this->appendChild($chapterPubLoc);
				} elseif ($jatsReference->getPublisherLoc() != '' && $jatsReference->getYear() == '') {
					$chapterPubLoc = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getPublisherName())) . ". ");
					$this->appendChild($chapterPubLoc);
				}

				if ($jatsReference->getYear() != "" && ($jatsReference->getFpage() != '' || $jatsReference->getLpage() != '')) {
					$chapterYear = $this->ownerDocument->createTextNode($jatsReference->getYear() . ":");
					$this->appendChild($chapterYear);
				} elseif ($jatsReference->getYear() != '') {
					$chapterYear = $this->ownerDocument->createTextNode($jatsReference->getYear() . ". ");
					$this->appendChild($chapterYear);
				}

				if ($jatsReference->getFpage() != '' && $jatsReference->getLpage() != '') {
					$chapterFpage = $this->ownerDocument->createTextNode($jatsReference->getFpage() . "-");
					$this->appendChild($chapterFpage);
				} elseif ($jatsReference->getFpage() != '' && $jatsReference->getLpage() == '') {
					$chapterFpage = $this->ownerDocument->createTextNode($jatsReference->getFpage() . ".");
					$this->appendChild($chapterFpage);
				}

				if ($jatsReference->getLpage()) {
					$chapterLpage = $this->ownerDocument->createTextNode($jatsReference->getLpage() . ". ");
					$this->appendChild($chapterLpage);
				}

				ReferenceMethods::extractLinks($jatsReference, $this);
				break;

			case "JATSParser\Back\Conference":

				/* @var $jatsReference Conference */
				// extracting reference authors

				ReferenceMethods::extractAuthors($jatsReference, $this);

				if ($jatsReference->getTitle() != '') {
					$chapterTitle = $this->ownerDocument->createTextNode(" " . htmlspecialchars(trim($jatsReference->getTitle())) . ". ");
					$this->appendChild($chapterTitle);
				}

				if ($jatsReference->getConfName() != '') {
					$conferenceTitle = $this->ownerDocument->createTextNode("Paper presented at: " . htmlspecialchars(trim($jatsReference->getTitle())) . "; ");
					$this->appendChild($conferenceTitle);
				}

				if ($jatsReference->getConfDate() != '' && $jatsReference->getYear() != '' && $jatsReference->getConfLoc() != '') {
					$conferenceDate = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getConfDate())) . ", ");
					$this->appendChild($conferenceDate);
				} elseif ($jatsReference->getConfDate() != '' && $jatsReference->getYear() == '' && $jatsReference->getConfLoc() != '') {
					$conferenceDate = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getConfDate())) . "; ");
					$this->appendChild($conferenceDate);
				} elseif ($jatsReference->getConfDate() != '' && $jatsReference->getYear() == '' && $jatsReference->getConfLoc() == '') {
					$conferenceDate = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getConfDate())) . ". ");
					$this->appendChild($conferenceDate);
				}

				if ($jatsReference->getYear() != '' && $jatsReference->getConfLoc() != '') {
					$conferenceYear = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getYear())) . '; ');
					$this->appendChild($conferenceYear);
				} elseif ($jatsReference->getYear() != '' && $jatsReference->getConfLoc() == '') {
					$conferenceYear = $this->ownerDocument->createTextNode(htmlspecialchars(trim($jatsReference->getYear())) . '. ');
					$this->appendChild($conferenceYear);
				}


				ReferenceMethods::extractLinks($jatsReference, $this);
				break;


		}
	}

}